<?php
	/**
	 * @class  allianceController
	 * @author SMaker (dowon2308@paran.com)
	 * @brief  aliianceXE 모듈의 controller class
	 **/

	// load API Class
	require_once _XE_PATH_.'modules/alliance/alliance.controller.api.php';

	class allianceController extends allianceControllerAPI {

		/**
		 * @brief 초기화
		 */
		function init() {
			// 비정상적인 접근 차단
			if($this->module_info->module != 'alliance') return new Object(-1, 'msg_invalid_request');

			// 템플릿 경로 지정
			$this->setTemplatePath($this->module_path.'tpl');
		}

		/**
		 * @brief 부모 사이트 등록
		 */
		function insertParentSite($domain, $title, $accept = false) {
			return $this->insertSite($domain, $title, 'P', $accept);
		}

		/**
		 * @brief 자식 사이트 등록
		 */
		function insertChildSite($domain, $title, $accept = false) {
			return $this->insertSite($domain, $title, 'C', $accept);
		}

		/**
		 * @brief 친구 사이트 등록
		 */
		function insertFriendSite($domain, $title, $accept = false) {
			return $this->insertSite($domain, $title, 'F', $accept);
		}

		/**
		 * @brief 사이트 등록
		 */
		function insertSite($domain, $title, $type, $accept = false) {
			if(!$domain) return;

			$args->site_srl = getNextSequence();
			$args->domain = $this->cleanDomain($domain);
			$args->type = $type;
			$args->title = $title;
			$args->status = $accept ? 'accepted' : 'waiting';
			$args->list_order = getNextSequence() * -1;
			$args->regdate = date('YmdHis');

			return executeQuery('alliance.insertSite', $args);
		}

		/**
		 * @brief 메시지 기록
		 */
		function insertMessage($obj) {
			$this->_filter($obj->type, array('S', 'R'), 'R');
			$this->_filter($obj->is_readed, array('Y', 'N'), 'N');

			$args->message_srl = $obj->message_srl ? $obj->message_srl : getNextSequence();
			$args->domain = $this->cleanDomain($obj->domain);
			$args->type = $obj->type;
			$args->is_readed = $obj->is_readed;
			$args->title = $obj->title;
			$args->content = $obj->content;

			return executeQuery('alliance.insertMessage', $args);
		}

		/**
		 * @brief 연합 정보 등록
		 */
		function insertAllianceInfo($obj) {
			$args->parent_domain = $this->cleanDomain($obj->parent_domain);
			$args->title = trim($obj->title);
			return executeQuery('alliance.insertAllianceInfo', $args);
		}

		/**
		 * @brief 연합 정보 업데이트
		 */
		function updateAllianceInfo($obj) {
			$args->parent_domain = $this->cleanDomain($obj->parent_domain);
			$args->title = trim($obj->title);
			return executeQuery('alliance.updateAllianceInfo', $args);
		}

		/**
		 * @brief 연합 정보 삭제
		 */
		function deleteAllianceInfo() {
			return executeQuery('alliance.deleteAllianceInfo');
		}

		/**
		 * @brief 연합 메시지 삭제
		 */
		function deleteMessage($message_srl) {
			$args->message_srl = $message_srl;
			return executeQuery('alliance.deleteMessage', $args);
		}

		/**
		 * @brief 다수의 연합 메시지 삭제
		 */
		function deleteMessages($message_srl) {
			$message_srls = is_array($message_srl) ? implode(',', $message_srl) : $message_srl;

			$args->message_srl = $message_srls;
			return executeQuery('alliance.deleteMessages', $args);
		}

		/**
		 * @brief 초대 메시지 삭제
		 */
		function deleteInvitationMessages($domain) {
			$args->domain = $domain;
			return executeQuery('alliance.deleteInvitationMessages', $args);
		}

		/**
		 * @brief 수락 상태로 변경
		 */
		function acceptSite($domain) {
			$args->domain = $domain;
			return executeQuery('alliance._acceptRequest', $args);
		}

		/**
		 * @brief 거절 상태로 변경
		 */
		function refuseSite($domain) {
			$args->domain = $domain;
			return executeQuery('alliance._refuseRequest', $args);
		}

		/**
		 * @brief 일치하는 사이트 삭제
		 */
		function deleteSite($site_srl) {
			$args->site_srl = $site_srl;
			return executeQuery('alliance.deleteSite', $args);
		}

		/**
		 * @brief 도메인이 일치하는 사이트 삭제
		 */
		function deleteSiteByDomain($domain) {
			$args->domain = $domain;
			return executeQuery('alliance.deleteSite', $args);
		}

		/**
		 * @brief 사이트 동기화 상태 변경
		 */
		function updateSiteStatus($domain, $status) {
			if(!$domain || !$status) return;

			$args->domain = $domain;
			$args->status = $status;
			return executeQuery('alliance.updateSiteStatus', $args);
		}

		/**
		 * @brief 회원 DB에 등록
		 */
		function insertMember($member_info, $site_srl) {
			$args->site_srl = $site_srl;
			$args->target_srl = $member_info->member_srl;
			$args->member_id = md5($member_info->member_id ? $member_info->member_id : getMicroTime());
			$args->regdate = date('YmdHis');
			return executeQuery('alliance.insertMember', $args);
		}

		/**
		 * @brief 인증 DB에 등록
		 */
		function insertMemberAuth($member_info, $site_srl) {
			$args->member_srl = $member_info->member_srl;
			$args->site_srl = $site_srl;
			$args->authkey = md5($member_info->user_id.$member_info->password.date('YmdHis'));
			$args->user_id = $member_info->user_id;
			$args->regdate = date('YmdHis');
			return executeQuery('alliance.insertMemberAuth', $args);
		}

		/**
		 * @brief 콘텐츠 DB에 등록
		 */
		function insertContent($site_srl, $document_srl, $cid) {
			$args->site_srl = $site_srl;
			$args->target_srl = $document_srl;
			$args->content_id = $cid ? $cid : md5(getMicroTime());
			$args->regdate = date('YmdHis');
			return executeQuery('alliance.insertContent', $args);
		}

		/**
		 * @brief 로그인 후 호출되는 트리거
		 */
		function triggerAfterLogin(&$member_info) {
			/**
			 * @TODO : 다른 도메인 간에도 로그인 동기화를 가능하게 할 수 있는 방법이 필요함
			 */
			return new Object();

			// 가상 사이트 정보를 구함
			$site_module_info  = Context::get('site_module_info');

			// 가상 사이트이면 동기화 하지 않음
			if($site_module_info->site_srl > 0) return new Object();

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 모듈 설정을 구함
			$config = $oAllianceModel->getModuleConfig();
			if($config->sync->member_sync != 'Y') return new Object();

			// 하위 사이트를 구함
			$args->s_status = 'accepted';
			$site_list = $oAllianceModel->getSites($args);

			// 하위 사이트가 없으면 동기화 하지 않음
			if(!count($site_list)) return new Object();

			// 아무런 인증이 되어 있지 않으면 동기화 하지 않음
			$auth = $oAllianceModel->getMemberAuths($member_info->member_srl);
			if(!$auth) return new Object();

			// 피라미터
			$params['ui'] = $this->_encode($member_info->user_id);
			$params['pp'] = $this->_encode($member_info->password);

			foreach($auth as $key => $val) {
				$auths[] = $val->site_srl;
				$authkey[$val->site_srl] = $val->authkey;
			}

			// 로그인 동기화를 위한 쿠키 생성
			foreach($site_list as $key => $val) {
				// 해당 사이트에 인증이 되어 있지 않다면 쿠키를 생성하지 않음
				if(!in_array($val->site_srl, $auths)) continue;

				$cookie_name = md5(md5('alliancexe'.$_SERVER['REMOTE_ADDR']));
				$cookie_value = $authkey[$val->site_srl].'/'.md5(md5($_SERVER['HTTP_HOST']));

				setcookie($cookie_name, $cookie_value, time()+60*60*24, '/');
			}

			return new Object();
		}

		/**
		 * @brief 회원 가입 후 호출되는 트리거
		 */
		function triggerAfterSignUp(&$member_info) {
			// 가상 사이트 정보를 구함
			$site_module_info  = Context::get('site_module_info');

			// 가상 사이트이면 동기화 하지 않음
			if($site_module_info->site_srl > 0) return new Object();

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 모듈 설정을 구함
			$config = $oAllianceModel->getModuleConfig();

			// 회원 정보 동기화를 사용하지 않으면 동기화 하지 않음
			if($config->sync->member_sync != 'Y') return new Object();

			// 하위 사이트를 구함
			$args->s_status = 'accepted';
			$site_list = $oAllianceModel->getSites($args);

			// 동기화 할 사이트가 없으면 동기화 하지 않음
			if(!count($site_list)) return new Object();

			// 피라미터
			$params['ui'] = $this->_encode($member_info->user_id);
			$params['pp'] = $this->_encode($member_info->password);
			$params['ea'] = $this->_encode($member_info->email_address);
			$params['un'] = $this->_encode($member_info->user_name);
			$params['nn'] = $this->_encode($member_info->nick_name);
			$params['ci'] = md5(date('YmdHis'));

			// 회원 고유 ID를 구함
			$mid = md5(getMicroTime());

			// JSON class
			$this->loadJSON($json);

			// 각 사이트에 동기화 요청 보냄
			foreach($site_list as $key => $val) {
				$buff = $this->sendRequest('syncMember', $val->domain, $params);

				// 응답이 없다면 계속 진행
				if(!$buff) continue;

				// JSON Decode
				$output = $json->decode($buff);
				if(!$output) continue;

				// 동기화 실패했을 경우 계속 진행
				if($output->message != 'success') continue;

				// 회원 DB에 등록
				$this->insertMember($member_info, $val->site_srl);

				// 인증 DB에 등록
				$this->insertMemberAuth($member_info, $val->site_srl);
			}

			return new Object();
		}

		/**
		 * @brief 회원 탈퇴 시 호출되는 트리거
		 */
		function triggerDeleteMember(&$obj) {
			// 가상 사이트 정보를 구함
			$site_module_info  = Context::get('site_module_info');

			// 가상 사이트이면 동기화 하지 않음
			if($site_module_info->site_srl > 0) return new Object();

			$member_srl = $obj->member_srl;
			if(!$member_srl) return new Object();

			$args->member_srl = $member_srl;
			executeQuery('alliance.deleteMembers', $args);
			executeQuery('alliance.deleteMemberAuths', $args);

			return new Object();
		}

		/**
		 * @brief 회원 개인 메뉴 추가
		 */
		function triggerAddMemberMenu(&$obj) {
			// 로그인 하지 않은 경우 추가하지 않음
			if(!Context::get('is_logged')) return new Object();

			$oMemberController = &getController('member');
			$oMemberController->addMemberMenu('dispAllianceManageSync', 'cmd_manage_alliance_sync');

			return new Object();
		}

		/**
		 * @brief 콘텐츠 동기화
		 */
		function triggerInsertDocument(&$obj) {
			// 가상 사이트 정보를 구함
			$site_module_info  = Context::get('site_module_info');

			/**
			 * @TODO 가상 사이트일 경우에도 동기화 가능하도록 개선
			 */
			if($site_module_info->site_srl > 0) return new Object();

			// documentModel 객체 생성
			$oDocumentModel = getModel('document');

			// 비밀글일 경우 동기화 하지 않음
			if($obj->status != $oDocumentModel->getConfigStatus('secret')) return new Object();

			// 임시 저장글일 경우 동기화 하지 않음
			if($obj->status != $oDocumentModel->getConfigStatus('temp')) return new Object();

			// 공지글일 경우 동기화 하지 않음
			if($obj->is_notice == 'Y') return new Object();

			// 중복 등록되지 않도록 처리
			if($obj->call_by_alliancexe) return new Object();

			// allianceXE의 model class
			$oAllianceModel = getModel('alliance');

			// 모듈 설정을 구함
			$config = $oAllianceModel->getModuleConfig();

			// 콘텐츠 동기화를 사용하지 않으면 동기화 하지 않음
			if($config->sync->contents_sync != 'Y') return new Object();

			// 비회원 콘텐츠를 동기화 하도록 하지 않았다면 동기화 하지 않음
			if($config->sync->guest_sync != 'Y' && !$obj->member_srl) return new Object();

			// 하위 사이트를 구함
			$args->s_status = 'accepted';
			$site_list = $oAllianceModel->getSites($args);

			// 하위 사이트가 없으면 동기화 하지 않음
			if(!count($site_list)) return new Object();

			// 제외 모듈
			$exclude_module = array('ad', 'attendance', 'iconshop', 'memo', 'opage', 'page', 'kin', 'resource', 'textyle', 'recruit', 'issuetracker', 'planet');

			// moduleModel 객체 생성
			$oModuleModel = getModel('module');

			// 모듈 정보를 구함
			$module_srl = $obj->module_srl;
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

			// 제외 모듈에 포함되어 있으면 동기화 하지 않음
			if(in_array($module_info->module, $exclude_module)) return new Object();

			// 대상 모듈 정리
			$target_module_srl = array();
			if($config->sync->target_module_srl) $target_module_srl = explode(',', $config->sync->target_module_srl);

			// 대상 모듈에 포함되어 있는지 확인
			switch($config->sync->target) {
				case 'include':
					if(!in_array($module_srl, $target_module_srl)) return new Object();
					break;
				case 'exclude':
					if(in_array($module_srl, $target_module_srl)) return new Object();
					break;
			}

			// 콘텐츠를 구분하기 위해 cid를 구함
			$extra_vars = unserialize($obj->extra_vars);
			$cid = $extra_vars->cid;

			// 이미 동기화된 콘텐츠인지 화인
			if($cid) {
				// 이미 동기화된 콘텐츠이면 동기화 하지 않음
				if($AllianceModel->isExistsContent($cid)) return new Object();
			} else {
				// 새로 등록하는 콘텐츠이면 cid 발급
				$cid = md5(getMicroTime());
			}

			// 업로드된 파일이 있는지 구함
			$upload_args->document_srl = $obj->document_srl;
			$output = executeQueryArray('alliance.getUploadedFiles', $upload_args);

			// 업로드된 파일이 있으면 각 파일에 대한 경로 치환
			if($output->data) {
				foreach($output->data as $key => $val) {
					// 이미지/동영상등일 경우
					if($val->direct_download == 'Y') {
						$source_filename = substr($val->uploaded_filename,2);
						$target_filename = getFullUrl('').$source_filename;
						$obj->content = str_replace($source_filename, $target_filename, $obj->content);
					// binary 파일일 경우
					} else {
						$download_url = sprintf('?module=%s&amp;act=%s&amp;file_srl=%s&amp;sid=%s', 'file', 'procFileDownload', $val->file_srl, $val->sid);
						$obj->content = str_replace($download_url, Context::getRequestUri().$download_url, $obj->content);
					}
				}
			}

			// 중복 동기화 되지 않도록 콘텐츠 DB에 등록
			$this->insertContent(0, $obj->document_srl, $cid);

			// cid 반영
			if(!$obj->extra_vars->cid) {
				$cid_args->extra_vars = $cid;
				executeQuery('alliance.updateDocumentCid', $cid_args);
			}

			// IP가 없을 경우 접속자 IP로 대체
			if(!$obj->ipaddress) $obj->ipaddress = $_SERVER['REMOTE_ADDR'];

			// 문서 정보를 암호화 (정확한 전달 및 보안을 위함)
			$params['md'] = $mid = $this->_encode($module_info->mid);;
			$params['tt'] = $this->_encode($obj->title);
			$params['cc'] = $this->_encode($obj->content);
			$params['pp'] = $this->_encode($obj->password);
			$params['ui'] = $this->_encode($obj->user_id);
			$params['un'] = $this->_encode($obj->user_name);
			$params['nn'] = $this->_encode($obj->nick_name);
			$params['tg'] = $this->_encode($obj->tags);
			$params['ia'] = $this->_encode($obj->ipaddress);
			$params['ci'] = $cid;
			$params['call_by_alliancexe'] = 1;

			// 각 사이트에 동기화 요청 보냄
			foreach($site_list as $key => $val) $this->sendRequest('syncDocument', $val->domain, $params);

			return new Object();
		}

		/**
		 * @brief 콘텐츠 업데이트
		 */
		function triggerUpdateDocument(&$obj) {
			// 가상 사이트 정보를 구함
			$site_module_info  = Context::get('site_module_info');

			// 가상 사이트이면 동기화 하지 않음
			if($site_module_info->site_srl > 0) return new Object();

			// 비밀글일 경우 동기화 하지 않음
			//if($obj->is_secret == 'Y') return new Object();

			// 공지글일 경우 동기화 하지 않음
			if($obj->is_notice == 'Y') return new Object();

			// 중복 등록되지 않도록 처리
			if($obj->call_by_alliancexe) return new Object();

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 모듈 설정을 구함
			$config = $oAllianceModel->getModuleConfig();

			// 콘텐츠 동기화를 사용하지 않으면 동기화 하지 않음
			if($config->sync->contents_sync != 'Y') return new Object();

			// 비회원 콘텐츠를 동기화 하도록 하지 않았다면 동기화 하지 않음
			if($config->sync->guest_sync != 'Y' && !$obj->member_srl) return new Object();

			// 하위 사이트를 구함
			$args->s_status = 'accepted';
			$site_list = $oAllianceModel->getSites($args);

			// 하위 사이트가 없으면 동기화 하지 않음
			if(!count($site_list)) return new Object();

			// 제외 모듈
			$exclude_module = array('ad', 'attendance', 'iconshop', 'memo', 'opage', 'page');

			// module의 model class
			$oModuleModel = &getModel('module');

			// 모듈 정보를 구함
			$module_srl = $obj->module_srl;
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

			// 제외 모듈에 포함되어 있으면 동기화 하지 않음
			if(in_array($module_info->module, $exclude_module)) return new Object();

			// 대상 모듈 정리
			$target_module_srl = array();
			if($config->sync->target_module_srl) $target_module_srl = explode(',', $config->sync->target_module_srl);

			// 대상 모듈에 포함되어 있는지 확인
			switch($config->sync->target) {
				case 'include':
					if(!in_array($module_srl, $target_module_srl)) return new Object();
					break;
				case 'exclude':
					if(in_array($module_srl, $target_module_srl)) return new Object();
					break;
			}

			// 콘텐츠를 구분하기 위해 콘텐츠 ID를 구함
			$extra_vars = unserialize($obj->extra_vars);
			$cid = $extra_vars->cid;

			// 콘텐츠 ID를 찾지 못했다면 콘텐츠 DB에서 직접 찾음
			if(!$cid) {
				$content_args->document_srl = $obj->document_srl;
				$output = executeQuery('alliance.getContentIdByDocumentSrl', $content_args);
				$cid = $output->data->content_id;
			}

			// 이미 동기화된 콘텐츠인지 화인
			if($cid) {
				$isPublished = true;
			} else {
				// 새로 등록하는 콘텐츠이면 cid 발급
				$cid = md5(getMicroTime());
			}

			// 업로드된 파일이 있는지 구함
			$upload_args->document_srl = $obj->document_srl;
			$output = executeQueryArray('alliance.getUploadedFiles', $upload_args);

			// 업로드된 파일이 있으면 각 파일에 대한 경로 치환
			if($output->data) {
				foreach($output->data as $key => $val) {
					// 이미지/동영상등일 경우
					if($val->direct_download == 'Y') {
						$source_filename = substr($val->uploaded_filename,2);
						$target_filename = getFullUrl('').$source_filename;
						$obj->content = str_replace($source_filename, $target_filename, $obj->content);
					// binary 파일일 경우
					} else {
						$download_url = sprintf('?module=%s&amp;act=%s&amp;file_srl=%s&amp;sid=%s', 'file', 'procFileDownload', $val->file_srl, $val->sid);
						$obj->content = str_replace($download_url, Context::getRequestUri().$download_url, $obj->content);
					}
				}
			}


			// 동기화되지 않았다면 중복 동기화 되지 않도록 콘텐츠 DB에 등록
			if(!$isPublished) {
				$this->insertContent(0, $obj->document_srl, $cid);

				// cid 반영
				$cid_args->extra_vars = $cid;
				executeQuery('alliance.updateDocumentCid', $cid_args);
			}

			// 문서 정보를 암호화 (정확한 전달 및 보안을 위함)
			if(!$obj->ipaddress) $obj->ipaddress = $_SERVER['REMOTE_ADDR'];
			$params = array();
			$params['md'] = $this->_encode($module_info->mid);
			$params['tt'] = $this->_encode($obj->title);
			$params['cc'] = $this->_encode($obj->content);
			$params['pp'] = $this->_encode($obj->password);
			$params['ui'] = $this->_encode($obj->user_id);
			$params['un'] = $this->_encode($obj->user_name);
			$params['nn'] = $this->_encode($obj->nick_name);
			$params['tg'] = $this->_encode($obj->tags);
			$params['ia'] = $this->_encode($obj->ipaddress);
			$params['ci'] = $cid;
			$params['call_by_alliancexe'] = 1;

			// 각 사이트에 동기화 요청 보냄
			foreach($site_list as $key => $val) {
				$requestType = $isPublished ? 'updateDocument' : 'syncDocument';
				$this->sendRequest($requestType, $val->domain, $params);
			}

			return new Object();
		}

		/**
		 * @brief 콘텐츠 삭제
		 */
		function triggerDeleteDocument(&$obj) {
			$args->document_srl = $obj->document_srl;
			executeQuery('alliance.deleteContent', $args);

			return new Object();
		}
	}
?>