<?php
	/**
	 * @class  allianceAdminView
	 * @author SMaker (dowon2308@paran.com)
	 * @brief  allianceXE 모듈의 admin view class
	 **/

	class allianceAdminView extends alliance {

		/**
		 * @brief 초기화
		 **/
		function init() {
			// template path 지정
			$this->setTemplatePath($this->module_path.'tpl');

			$m_act = array('dispAllianceAdminManageMessages', 'dispAllianceAdminInsertMessage', 'dispAllianceAdminDeleteMessage');
			$s_act = array('dispAllianceAdminManageSites', 'dispAllianceAdminInvite', 'dispAllianceAdminAccept', 'dispAllianceAdminRefuse');
			$c_act = array('dispAllianceAdminManageContents', 'dispAllianceAdminDeleteContents', 'dispAllianceAdminCancleContentSync');
			$u_act = array('dispAllianceAdminManageMember', 'dispAllianceAdminDeleteMember');
			$l_act = array('dispAllianceAdminManageLogs', 'dispAllianceAdminDeleteLog');

			Context::set('m_act', $m_act);
			Context::set('s_act', $s_act);
			Context::set('c_act', $c_act);
			Context::set('u_act', $u_act);
			Context::set('l_act', $l_act);

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 연합 정보를 구함
			$this->alliance_info = $alliance_info = $oAllianceModel->getAllianceInfo();

			// 연합 관리자 권한이 있는지 판별
			$grant = Context::get('grant');
			$grant->_manager = $oAllianceModel->isGranted($alliance_info);
			$grant->_member = $alliance_info ? true : false;
			$grant->_create = $alliance_info ? false : true;

			$this->grant = $grant;
			Context::set('grant', $grant);
		}

		/**
		 * @brief 초기 화면 (요약)
		 **/
		function dispAllianceAdminContent() {
			// 템플릿에서 쓸 수 있도록 Context::set()
			Context::set('alliance_info', $this->alliance_info);

			// 초대 메시지를 구함
			$args->type = 'R';
			$output = executeQuery('alliance.getInvitationCount', $args);
			Context::set('invitation_count', $output->data->count);

			// 최근 메시지를 구함
			$args->type = 'R';
			$args->order_type = 'desc';
			$args->list_count = 5;
			$output = executeQueryArray('alliance.getMessages', $args);
			Context::set('recently_messages', $output->data);

			// 템플릿 파일 지정
			$this->setTemplateFile('index');
		}

		/**
		 * @brief 연합 설정
		 */
		function dispAllianceAdminSetup() {
			// 연합 관리자 권한이 있을 경우 연합 설정을 구해옴
			if($this->grant->_manager) {
				// allianceXE의 model class
				$oAllianceModel = &getModel('alliance');

				// 연합 설정을 구함
				$config = $oAllianceModel->getModuleConfig();

				// 전체 사이트 수를 구함
				$args->s_status = 'accepted';
				$site_list = $oAllianceModel->getSites($args);

				$config->alliance_info->count = count($site_list) + 1;

				// 템플릿에서 쓸 수 있도록 Context::set()
				Context::set('config', $config);
			}

			// 템플릿 파일 지정
			$this->setTemplateFile('setup');
		}

		/**
		 * @brief 동기화 설정
		 */
		function dispAllianceAdminSyncSetup() {
			// 연합에 소속되어 있을 경우 동기화 설정을 구해옴
			if($this->alliance_info) {
				// allianceXE의 model class
				$oAllianceModel = &getModel('alliance');

				// 동기화 설정을 구함
				$config = $oAllianceModel->getModuleConfig();

				// 템플릿에서 쓸 수 있도록 Context::set()
				Context::set('config', $config->sync);
			}

			// 템플릿 파일 지정
			$this->setTemplateFile('sync_setup');
		}

		/**
		 * @brief 스킨 설정
		 */
		function dispAllianceAdminSkinSetup() {
			// module의 model class
			$oModuleModel = &getModel('module');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 모듈 설정을 구함
			$config = $oAllianceModel->getModuleConfig();

			// 스킨 목록을 구함
			$skin_list = $oModuleModel->getSkins($this->module_path);

			// 스킨의 XML 정보를 구함
			$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $config->skin->skin);

			// DB에 설정된 스킨 정보를 구함
			$skin_vars = unserialize($config->skin->extra_vars);

			if(count($skin_info->extra_vars)) {
				foreach($skin_info->extra_vars as $key => $val) {
					$group = $val->group;
					$name = $val->name;
					$type = $val->type;
					if(!$name) {
						unset($skin_info->extra_vars[$key]);
						continue;
					}
					$value = $skin_vars->{$name};
					if($type=='checkbox') $value = $value?unserialize($value):array();

					$skin_info->extra_vars[$key]->value= $value;
				}
			}

			// 템플릿에서 쓸 수 있도록 Context::set()
			Context::set('config', $config->skin);
			Context::set('skin_list', $skin_list);
			Context::set('skin_info', $skin_info);
			Context::set('skin_vars', $skin_vars);

			$this->setTemplateFile('skin_setup');
		}

		/**
		 * @brief 연합 정보
		 */
		function dispAllianceAdminInfo() {
			// 연합 정보를 구함
			$alliance_info = $this->alliance_info;

			// 연합 정보가 있을 경우
			if($alliance_info) {
				// 상위 사이트의 도메인을 보기 좋게 만듬
				$alliance_info->parent_domain = $this->arrangeDomain($alliance_info->parent_domain);
			}

			// 템플릿에서 쓸 수 있도록 Context::set()
			Context::set('alliance_info', $alliance_info);

			// 템플릿 파일 지정
			$this->setTemplateFile('info');
		}

		/**
		 * @brief 연합 메시지 관리
		 */
		function dispAllianceAdminManageMessages() {
			$iType = Context::get('iType');
			$this->_filter($iType, array('R', 'S'), 'R');

			Context::set('iType', $iType);

			$search_target = Context::get('search_target');
			$search_keyword = trim(Context::get('search_keyword'));

			switch($search_target) {
				case 'e_content':
				case 'content':
				case 'source':
				case 'domain':
				case 'less_date':
				case 'more_date':
					$args->{$search_target} = $search_keyword;
					break;
				case 'between_date':
					$args->{$search_target} = explode(',', $search_keyword);
					break;
			}

			$args->page = Context::get('page');
			$args->type = $iType;
			$args->order_type = 'desc';

			// 메시지 목록을 구함
			$output = executeQueryArray('alliance.getMessageList', $args);

			// 템플릿에서 쓸 수 있도록 Context::set()
			Context::set('message_list', $output->data);
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page_navigation', $output->page_navigation);
			Context::set('page', $output->page);

			$message_srl = (int)Context::get('message_srl');
			// 선택한 메시지가 있다면 해당 메시지에 대한 정보를 구함
			if($message_srl) {
				$args->message_srl = $message_srl;
				$output = executeQuery('alliance.getMessageInfo', $args);

				// 읽은 상태로 변경
				if($output->data->is_readed == 'N') executeQuery('alliance.chkMessage', $args);

				// <br /> 태그는 예외적으로 허용
				$output->data->content = str_replace('&lt;br /&gt;', '<br />', htmlspecialchars($output->data->content));

				// 템플릿에서 쓸 수 있도록 Context::set()
				Context::set('message_info', $output->data);
			}

			// 템플릿 파일 지정
			$this->setTemplateFile('manage_messages');
		}

		/**
		 * @brief 연합 사이트 관리
		 */
		function dispAllianceAdminManageSites() {
			// 연합 관리자일 경우
			if(!$this->grant->_create && $this->grant->_manager) {
				$iType = Context::get('iType');
				$this->_filter($iType, array('P', 'C'), 'C');
			} else {
				$iType = '';
			}
			Context::set('iType', $iType);

			$args->page = Context::get('page');
			$args->type = $iType;
			$args->order_type = 'desc';

			// 사이트 목록을 구함
			$output = executeQueryArray('alliance.getSiteList', $args);

			// 템플릿에서 쓸 수 있도록 Context::set()
			Context::set('site_list', $output->data);
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page_navigation', $output->page_navigation);
			Context::set('page', $output->page);

			// 템플릿 파일 지정
			$this->setTemplateFile('manage_sites');
		}

		/**
		 * @brief 콘텐츠 관리
		 */ 
		function dispAllianceAdminManageContents() {
			$sort_index = Context::get('sort_index');
			$order_type = Context::get('order_type');
			if(!in_array($sort_index, array('voted_count', 'readed_count', 'comment_count', 'list_order'))) $sort_index = 'list_order';
			if(!in_array($order_type, array('desc', 'asc'))) $order_type = 'asc';

			$sort_index = 'a.'.$sort_index;

			 // 등록된 컨텐츠 목록을 구함
			$args->page = Context::get('page');
			$args->sort_index = $sort_index;
			$args->order_type = $order_type;
			$output = executeQueryArray('alliance.getContentsList', $args);

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 사이트 목록을 구함
			$site_args->s_status = 'accepted';
			$site_list = $oAllianceModel->getSites($site_args);

			// 템플릿에서 쓸 수 있도록 Context::set()
			Context::set('contents_list', $output->data);
			Context::set('site', $site_list);
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page_navigation', $output->page_navigation);
			Context::set('page', $output->page);

			// 템플릿 파일 지정
			$this->setTemplateFile('manage_contents');
		}

		/**
		 * @brief 회원 관리
		 */
		function dispAllianceAdminManageMember() {
			 // 등록된 회원 목록을 구함
			$args->page = Context::get('page');
			$args->order_type = 'asc';
			$output = executeQueryArray('alliance.getMemberList', $args);

			// 템플릿에서 쓸 수 있도록 Context::set()
			Context::set('member_list', $output->data);
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page_navigation', $output->page_navigation);
			Context::set('page', $output->page);

			// 템플릿 파일 지정
			$this->setTemplateFile('manage_members');
		}

		/**
		 * @brief 로그 관리
		 */
		function dispAllianceAdminManageLogs() {
			$args->page = Context::get('page');
			$output = executeQuery('alliance.getLogList', $args);

			Context::set('log_list', $output->data);
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page_navigation', $output->page_navigation);
			Context::set('page', $output->page);

			$this->setTemplateFile('manage_logs');
		}

		/**
		 * @brief 사이트 초대
		 */
		function dispAllianceAdminInvite() {
			// 템플릿 파일 지정
			$this->setTemplateFile('invite');
		}

		/**
		 * @brief 초대 수락
		 */
		function dispAllianceAdminAccept() {
			$domain = Context::get('domain');
			if(!$domain) return new Object(-1, 'msg_invalid_request');

			// 템플릿 파일 지정
			$this->setTemplateFile('accept');
		}

		/**
		 * @brief 연합 메시지 보내기
		 */
		function dispAllianceAdminInsertMessage() {
			$this->setTemplateFile('insert_message');
		}

		/**
		 * @brief 연합 메시지 삭제
		 */
		function dispAllianceAdminDeleteMessage() {
			$message_srl = (int)Context::get('message_srl');
			if($message_srl) {
				$args->message_srl = $message_srl;
				$output = executeQuery('alliance.getMessageInfo', $args);

				Context::set('message_info', $output->data);
			} else {
				return new Object(-1, 'msg_invalid_request');
			}

			$this->setTemplateFile('delete_message');
		}

		/**
		 * @brief 사이트 정보 수정
		 */
		function dispAllianceAdminModifySite() {
			$site_srl = (int)Context::get('site_srl');
			if(!$site_srl) return new Object(-1, 'msg_invalid_request');

			$args->site_srl = $site_srl;
			$output = executeQuery('alliance.getSiteInfo', $args);

			Context::set('site_info', $output->data);

			$this->setTemplateFile('modify_site');
		}

		/**
		 * @brief 연합 만들기
		 */
		function dispAllianceAdminCreate() {
			$this->setTemplateFile('create');
		}

		/**
		 * @brief 연합 해체
		 */
		function dispAllianceAdminBreakup() {
			Context::set('alliance_info', $this->alliance_info);
			$this->setTemplateFile('breakup');
		}

		/**
		 * @brief 연합 탈퇴
		 */
		function dispAllianceAdminLeave() {
			$this->setTemplateFile('leave');
		}

		/**
		 * @brief 연합 추방
		 */
		function dispAllianceAdminKick() {
			$site_srl = (int)Context::get('site_srl');
			if(!$site_srl) return new Object(-1, 'msg_invalid_request');

			$oAllianceModel = &getModel('alliance');
			$site_info = $oAllianceModel->getSiteInfoBySiteSrl($site_srl);

			Context::set('site_info', $site_info);
			Context::set('alliance_info', $this->alliance_info);

			// 템릿 파일 지정
			$this->setTemplateFile('kick');
		}

		/**
		 * @brief 콘텐트 완전 삭제
		 */
		function dispAllianceAdminDeleteContent() {
			$document_srl = (int)Context::get('content_srl');
			if(!$document_srl) return new Object(-1, 'msg_invalid_request');

			$args->document_srl = $document_srl;
			$output = executeQuery('alliance.getContentInfo', $args);
			if(!$output->data) return new Object(-1, 'msg_invalid_request');

			Context::set('content_info', $output->data);

			$this->setTemplateFile('delete_content');
		}

		/**
		 * @brief 콘텐트 발행 취소
		 */
		function dispAllianceAdminCancleContentSync() {
			$document_srl = (int)Context::get('content_srl');
			if(!$document_srl) return new Object(-1, 'msg_invalid_request');

			$args->document_srl = $document_srl;
			$output = executeQuery('alliance.getContentInfo', $args);
			if(!$output->data) return new Object(-1, 'msg_invalid_request');

			Context::set('content_info', $output->data);

			$this->setTemplateFile('cancle_content_sync');
		}

		/**
		 * @brief 사이트 초대 거부
		 */
		function dispAllianceAdminRefuse() {
			$domain = Context::get('domain');
			if(!$domain) return new Object(-1, 'msg_invalid_request');

			// 템플릿 파일 지정
			$this->setTemplateFile('refuse');
		}
	}
?>