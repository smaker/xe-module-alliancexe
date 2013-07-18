<?php
	/**
	 * @class  allianceAdminView
	 * @author SMaker (dowon2308@paran.com)
	 * @brief  allianceXE 모듈의 admin controller class
	 **/

	class allianceAdminController extends alliance {

		/**
		 * @brief 연합 설정 저장
		 */
		function procAllianceAdminInsertConfig() {
			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 연합 정보를 구함
			$alliance_info = $oAllianceModel->getAllianceInfo();

			// 연합 관리자 권한이 없으면 에러
			if(!$oAllianceModel->isGranted($alliance_info)) return new Object(-1, 'msg_invalid_access');

			// 넘어온 설정을 구함
			$args = Context::getRequestVars();

			// 불필요한 값 제거
			unset($args->body);
			unset($args->_filter);
			unset($args->module);
			unset($args->act);

			// 넘어온 값 정리
			$this->_trim($args->alliance_title);
			$this->_int($args->count);

			if($args->count < 1) $args->count = 1;

			// 모듈 설정을 구함
			$config = $oAllianceModel->getModuleConfig();

			// 연합 정보를 구함
			$alliance_info = $oAllianceModel->getAllianceInfo();

			$alliance_title = $config->alliance_info->title = $args->alliance_title;
			$alliance_count = $alliance_info->count;

			// 연합 설정 저장
			$oModuleController = &getController('module');
			$output = $oModuleController->insertModuleConfig('alliance', $config);
			if(!$output->toBool()) return $output;

			$current_url = $this->current_url;

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			// 연합 정보가 있다면
			if($alliance_info) {
				// 연합 정보 업데이트
				$args->parent_domain = $current_url;
				$args->title = $alliance_title;
				$args->count = $alliance_count;
				$oAllianceController->updateAllianceInfo($args);

				// 하위 사이트를 구함
				$site_args->s_status = 'accepted';
				$site_list = $oAllianceModel->getSites($site_args);

				// 하위 사이트가 있다면 동기화 요청 보냄
				if(count($site_list)) {
					$params['ut'] = $this->_encode($config); // << 피라미터

					// 각 사이트에 동기화 요청 보냄
					foreach($site_list as $key => $val) $oAllianceController->sendRequest('syncConfig', $val->domain, $params);
				}
			}
			}

			$this->setMessage('succeed_saved');
		}


		/**
		 * @brief 동기화 설정 저장
		 */
		function procAllianceAdminInsertSyncConfig() {
			// 입력한 설정을 구함
			$args = Context::getRequestVars();

			// module의 model class
			$oModuleModel = &getModel('module');

			// 다른 설정들과 결합
			$config = $oModuleModel->getModuleConfig('alliance');
			$config->sync->contents_sync = $args->contents_sync; // << 콘텐츠 동기화 (보내기)
			$config->sync->contents_sync_r = $args->contents_sync_r; // << 콘텐츠 동기화 (받기)
			$config->sync->member_sync = $args->member_sync; // << 회원 동기화 (보내기)
			$config->sync->member_sync_r = $args->member_sync_r; // << 회원 동기화 (받기)
			$config->sync->guest_sync = $args->guest_sync; // << 비회원 콘텐츠 동기화 (보내기)
			$config->sync->guest_sync_r = $args->guest_sync_r; // << 비회원 콘텐츠 동기화 (받기)
			$config->sync->target = $args->target; // << 대상
			$config->sync->target_module_srl = $args->target_module_srl; // << 대상 모듈
			$config->sync->contents_title_prefix_r = $args->contents_title_prefix_r; // << 콘텐츠 제목 머릿말 (받기)

			// 연합 설정 저장
			$oModuleController = &getController('module');
			$output = $oModuleController->insertModuleConfig('alliance', $config);
			if(!$output->toBool()) return $output;

			// 메시지 지정
			$this->setMessage('succeed_saved');
		}

		/**
		 * @brief 연합 메시지 보내기
		 */
		function procAllianceAdminInsertMessage() {
			// 메시지 발송에 필요한 정보를 구함
			$title = trim(Context::get('title'));
			$message = trim(Context::get('content'));

			// 메시지 발송에 필요한 정보가 넘어오지 않았다면 에러
			if(!$title || !$message) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 연합 정보를 구함
			$alliance_info = $oAllianceModel->getAllianceInfo();

			// 연합에 소속되어 있지 않으면 에러
			if(!$alliance_info) return new Object(-1, 'msg_cannot_send_message');

			// 메시지를 보낼 수 있는 사이트 목록을 구함
			$args->s_status = 'accepted';
			$site_list = $oAllianceModel->getSites($args);

			// 보낼 수 있는 사이트가 없으면 에러
			if(!count($site_list)) return new Object(-1, 'msg_cannot_send_message');

			$params['title'] = $title;
			$params['content'] = $message;

			$total = $success = count($site_list);
			$failed = 0;

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			// JSON class
			$oAllianceController->loadJSON($json);

			// 연합에 소속된 각 사이트에 메시지 보냄
			foreach($site_list as $key => $val) {
				$domain = $val->domain;
				$buff = $oAllianceController->sendRequest('message', $domain, $params);

				// JSON 디코딩
				$output = $json->decode($buff);
				if(!$output || $output->message != 'success'){
					$success--;
					$failed++;
				}
			}

			// 메시지 발송 흔적을 남김 
			$args->domain = $domain;
			$args->type = 'S';
			$args->title = $title;
			$args->content = $message;
			$output = $oAllianceController->insertMessage($args);
			if(!$output->toBool()) return $output;

			// 성공 유무에 따른 메시지 차별화
			if($success < 1) {
				$message = sprintf(Context::getLang('succeed_alliance_message_sent__'), $total);
			} else {
				if($failed < 1) $message = Context::getLang('succeed_alliance_message_sent');
				else $message = sprintf(Context::getLang('succeed_alliance_message_sent_'), $failed);
			}

			// 메시지 지정
			$this->setMessage($message);
		}

		/**
		 * @brief 사이트 초대
		 **/
		function procAllianceAdminInvite() {
			$domain = Context::get('domain');
			$title = trim(Context::get('title'));

			// 도메인이 넘어오지 않았다면 에러
			if(!$domain) return new Object(-1, 'msg_invalid_domain');

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			// 요청 보냄
			$buff = $oAllianceController->sendRequest('invite', $domain);

			// 응답이 없으면 에러
			if(!$buff) return new Object(-1, 'msg_failed_invite');

			// JSON class
			$oAllianceController->loadJSON($json);

			// JSON 디코딩
			$output = $json->decode($buff);

			// API 요청 성공 시
			if($output->message == 'success') {
				// 하위 사이트 등록
				$output = $oAllianceController->insertChildSite($domain, $title);
				if(!$output->toBool()) return $output;

				// 보낸 메시지 등록
				$args->domain = $this->cleanDomain($domain);
				$args->type = 'S';
				$args->title = 'Invitation Message';
				$args->content = '#invite';
				$oAllianceController->insertMessage($args);
			} else {
				return new Object(-1, $output->message ? $output->message : 'msg_failed_invite');
			}

			// 메시지 지정
			$this->setMessage('succeed_invite');
		}

		/**
		 * @brief 사이트 초대 수락
		 */
		function procAllianceAdminAccept() {
			$domain = Context::get('domain');

			// 넘어온 도메인이 없으면 에러
			if(!$domain) return new Object(-1, 'msg_invalid_domain');

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			// 요청을 보냄
			$buff = $oAllianceController->sendRequest('accept', $domain);
			if(!$buff) return new Object(-1, 'msg_failed_accept');

			// JSON class
			$oAllianceController->loadJSON($json);

			// JSON 디코딩
			$output = $json->decode($buff);

			// 성공 시
			if($output->message == 'success') {
				$alliance_info = $output->alliance_info;

				// 연합 이름을 구함
				$alliance_title = $alliance_info->title->body;
				$count = $alliance_info->count->body + 1;

				// 트랜젝션 시작
				$oDB = &DB::getInstance();
				$oDB->begin();

				// 연합 정보를 DB에 등록
				$args->parent_domain = $domain;
				$args->title = $alliance_title;
				$args->count = $count;
				$output = $oAllianceController->insertAllianceInfo($args);

				// 에러 발생 시 되돌림
				if(!$output->toBool()) {
					$oDB->rollback();
					return new Object(-1, 'msg_failed_accept');
				}

				// 수락 상태로 변경
				$output = $oAllianceController->acceptSite($domain);
				if(!$output->toBool()) {
					$oDB->rollback();
					return new Object(-1, 'msg_failed_accept');
				}

				// 메시지 등록
				$args->domain = $this->cleanDomain($domain);
				$args->type = 'S';
				$args->title = 'Acceptance Message';
				$args->content = '#accept';
				$output = $oAllianceController->insertMessage($args);
				if(!$output->toBool()) {
					$oDB->rollback();
					return $output;
				}

				// 다른 사이트가 있다면 추가
				if($alliance_info->site_list) {
					foreach($alliance_info->site_list->site as $key => $val) {
						$title = $val->attrs->title;
						$domain = $val->attrs->domain;

						// 자기 자신은 추가하지 않음
						if($domain == $this->current_url) continue;

						// 친구 사이트로 추가
						$output = $oAllianceController->insertFriendSite($domain, $title, true);
						if(!$output->toBool()) {
							$oDB->rollback();
							return $output;
						}
					}
				}

				// commit
				$oDB->commit();
			} else {
				return new Object(-1, $output->message);
			}

			// 초대 메시지 삭제
			$oAllianceController->deleteInvitationMessages($domain);

			// 메시지 지정
			$this->setMessage('succeed_accept');
		}

		/**
		 * @brief 사이트 초대 거부
		 */
		function procAllianceAdminRefuse() {
			$domain = Context::get('domain');
			if(!$domain) return new Object(-1, 'msg_invalid_domain');

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			// 요청을 보냄
			$buff = $oAllianceController->sendRequest('refuse', $domain);
			if(!$buff) return new Object(-1, 'msg_failed_refused');

			// JSON class
			$oAllianceController->loadJSON($json);

			// JSON 디코딩
			$output = $json->decode($buff);

			// 성공 시에
			if($output->message == 'success') {
				// 메시지 등록
				$args->domain = $this->cleanDomain($domain);
				$args->type = 'S';
				$args->title = 'Refusal Message';
				$args->content = '#refuse';
				$output = $oAllianceController->insertMessage($args);
				if(!$output->toBool()) return $output;
			} else {
				return new Object(-1, $output->message);
			}

			// 메시지 지정
			$this->setMessage('succeed_refused');
		}

		/**
		 * @brief 사이트 제목 수정
		 */
		function procAllianceAdminModifySite() {
			$site_srl = (int)Context::get('site_srl');
			if(!$site_srl) return new Object(-1, 'msg_invalid_request');

			$title = trim(Context::get('title'));
			if(!$title) return new Object(-1, 'msg_input_title');

			$args->site_srl = $site_srl;
			$args->title = $title;
			$output = executeQuery('alliance.updateSite', $args);
			if(!$output->toBool()) return $output;

			// 완료 후 이동할 Action명이 있다면 지정
			$redirect_act = Context::get('redirect_act');
			$this->add('redirect_act', $redirect_act);

			// 메시지 수정
			$this->setMessage('success_updated');
		}

		/**
		 * @brief 연합 메시지 삭제
		 */
		function procAllianceAdminDeleteMessage() {
			$message_srl = (int)Context::get('message_srl');
			if(!$message_srl) return new Object(-1, 'msg_invalid_request');

			$args->message_srl = $message_srl;
			$output = executeQuery('alliance.deleteMessage', $args);
			if(!$output->toBool()) return $output;

			$this->setMessage('success_deleted');
		}

		/**
		 * @brief 다수의 연합 메시지 삭제
		 */
		function procAllianceAdminDeleteMessages() {
			$message_srls = Context::get('cart');
			if(!$message_srls) return new Object(-1, 'msg_not_exists_selected_messages');

			$message_srl = explode('|@|', $message_srls);
			$message_srl = implode(',', $message_srl);

			$args->message_srl = $message_srl;
			$output = executeQuery('alliance.deleteMessages', $args);
			if(!$output->toBool()) return $output;

			$this->setMessage('success_deleted');
		}

		/**
		 * @brief 스킨 정보 업데이트
		 **/
		function procAllianceAdminUpdateSkinInfo() {
			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 기존의 설정을 구함
			$config = $oAllianceModel->getModuleConfig();

			$skin = $config->skin->skin;

			// module의 model class
			$oModuleModel = &getModel('module');

			// 스킨의 정보를 구해옴 (extra_vars를 체크하기 위해서)
			$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);
			$skin_vars = $config->skin->extra_vars;

			// 입력받은 변수들을 체크 (act, module_srl, page, mid, module등 기본적인 변수들 없앰)
			$obj = Context::getRequestVars();
			unset($obj->_filter);
			unset($obj->body);
			unset($obj->act);
			unset($obj->page);
			unset($obj->mid);
			unset($obj->module);

			// 원 skin_info에서 extra_vars의 type이 image일 경우 별도 처리를 해줌
			if($skin_info->extra_vars) {
				foreach($skin_info->extra_vars as $vars) {
					if($vars->type!='image') continue;

					$image_obj = $obj->{$vars->name};

					// 삭제 요청에 대한 변수를 구함
					$del_var = $obj->{"del_".$vars->name};
					unset($obj->{"del_".$vars->name});
					if($del_var == 'Y') {
						FileHandler::removeFile($skin_vars[$vars->name]->value);
						continue;
					}

					// 업로드 되지 않았다면 이전 데이터를 그대로 사용
					if(!$image_obj['tmp_name']) {
						$obj->{$vars->name} = $skin_vars[$vars->name]->value;
						continue;
					}

					// 정상적으로 업로드된 파일이 아니면 무시
					if(!is_uploaded_file($image_obj['tmp_name'])) {
						unset($obj->{$vars->name});
						continue;
					}

					// 이미지 파일이 아니어도 무시
					if(!preg_match("/\.(jpg|jpeg|gif|png)$/i", $image_obj['name'])) {
						unset($obj->{$vars->name});
						continue;
					}

					// 경로를 정해서 업로드
					$path = './files/attach/images/alliance/';

					// 디렉토리 생성
					if(!FileHandler::makeDir($path)) return false;

					$filename = $path.$image_obj['name'];

					// 파일 이동
					if(!move_uploaded_file($image_obj['tmp_name'], $filename)) {
						unset($obj->{$vars->name});
						continue;
					}

					// 정상 파일 업로드 
					FileHandler::removeFile($skin_vars[$vars->name]->value);

					// 변수를 바꿈
					unset($obj->{$vars->name});
					$obj->{$vars->name} = $filename;
				}
			}

			// 스킨 설정에 수정 사항 반영
			$config->skin->extra_vars = serialize($obj);

			// module의 controller class
			$oModuleController = &getController('module');

			// 수정
			$oModuleController->insertModuleConfig('alliance', $config);

			$this->setLayoutPath('./common/tpl');
			$this->setLayoutFile('default_layout.html');
			$this->setTemplatePath('./modules/module/tpl');
			$this->setTemplateFile("top_refresh.html");
		}

		/**
		 * @brief 콘텐츠 완전 삭제
		 */
		function procAllianceAdminDeleteContent() {
			$content_id = Context::get('content_id');
			if(!$content_id) return new Object(-1, 'msg_invalid_request');

			// 넘어온 콘텐츠 ID로 문서 번호를를 구함
			$content_args->content_id = $content_id;
			$output = executeQuery('alliance.getDocumentSrlByContentId', $content_args);
			if(!$output->toBool()) return $output;

			$document_srl = $output->data->document_srl;

			// document의 controller class
			$oDocumentController = &getController('document');

			// 문서 삭제
			$output = $oDocumentController->deleteDocument($document_srl, true);
			if(!$output->toBool()) return $output;

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 연합 정보를 구함
			$alliance_info = $oAllianceModel->getAllianceInfo();

			// 연합 정보가 없다면 에러
			if(!$alliance_info) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			$params['content_id'] = $content_id;

			// 사이트를 구함
			$args->s_status = 'accepted';
			$site_list = $oAllianceModel->getSites($args);

			// 각 사이트에 요청을 보냄
			if(count($site_list)) {
				foreach($site_list as $key => $val) {
					$oAllianceController->sendRequest('deleteDocument', $val->domain, $params);
				}
			}

			// 메시지 지정
			$this->setMessage('succeed_delete');
		}

		/**
		 * @brief 콘텐츠 발행 취소
		 */
		function procAllianceAdminCancleContentSync() {
			$content_id = Context::get('content_id');
			if(!$content_id) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 연합 정보를 구함
			$alliance_info = $oAllianceModel->getAllianceInfo();

			// 연합 정보가 없다면 에러
			if(!$alliance_info) return new Object(-1, 'msg_invalid_request');

			// 사이트를 구함
			$args->s_status = 'accepted';
			$site_list = $oAllianceModel->getSites($args);
			if(count($site_list)) {
				$params['content_id'] = $content_id;

				foreach($site_list as $key => $val) {
					$oAllianceController->sendRequest('deleteDocument', $val->domain, $params);
				}
			}

			// 메시지 지정
			$this->setMessage('succeed_delete');
		}

		/**
		 * @brief 연합 생성
		 */
		function procAllianceAdminCreate() {
			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 연합 정보를 구함
			$alliance_info = $oAllianceModel->getAllianceInfo();

			// 이미 연합에 소속되어 있으면 에러
			if($alliance_info) return new Object(-1, 'msg_invalid_access');

			// 넘어온 설정을 구함
			$args = Context::getRequestVars();

			// 넘어온 값 정리
			$this->_trim($args->alliance_title);
			$config->alliance_info->title = $args->alliance_title;
			$config->alliance_info->count = 1;

			// 연합 설정 저장
			$oModuleController = &getController('module');
			$output = $oModuleController->insertModuleConfig('alliance', $config);
			if(!$output->toBool()) return $output;

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			// 연합 정보 등록
			$current_url = $this->current_url;
			$args->parent_domain = $current_url;
			$args->title = $args->alliance_title;
			$args->count = 1;
			$oAllianceController->insertAllianceInfo($args);

			$this->setMessage('succeed_created');
		}

		/**
		 * @brief 연합 탈퇴
		 */
		function procAllianceAdminLeave($alliance_info = null) {
			@set_time_limit(0);

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			// 연합 정보를 구함
			if(!$alliance_info) $alliance_info = $oAllianceModel->getAllianceInfo();

			// 연합 관리자 권한이 있으면 탈퇴할 수 없다
			if($oAllianceModel->isGranted($alliance_info)) return new Object(-1, 'msg_not_permitted');

			// 등록된 사이트 목록을 구함
			$site_list = $oAllianceModel->getSites($args);

			// 탈퇴 통보를 보냄
			if(count($site_list)) {
				foreach($site_list as $key => $val) {
					$oAllianceController->sendRequest('leave', $val->domain);
				}
			}

			// document의 controller class
			$oDocumentController = &getController('document');

			// 등록된 콘텐츠를 500개 단위로 끊어서 삭제
			$page = 1;
			do {
				$args->page = $page;
				$output = executeQueryArray('alliance.getAllContents', $args);
				if(!$output->toBool()) return $output;

				if($output->data) {
					$document_srls = array();

					foreach($output->data as $key => $val) {
						if(!$val->document_srl) continue;
						if($val->site_srl < 1) {
							$document_srls[] = $document_srl;
							continue;
						}
						$document_srl = $val->target_srl;
						$document_srls[] = $document_srl;
						$output = $oDocumentController->deleteDocument($document_srl, true);
						if(!$output->toBool()) return $output;
					}

					if(count($document_srls)) {
						$document_srls = implode(',', $document_srls);

						$args->document_srls = $document_srls;
						$output = executeQueryArray('alliance.deleteContents', $args);
						if(!$output->toBool()) return $output;
					} else {
						if(!$output->page_navigation->getNextPage()) break;
					}
				}

				$page++;
			} while($output->data && $output->page_navigation->getNextPage());

			// 연합 정보 삭제
			$oAllianceController->deleteAllianceInfo();

			// 등록된 모든 연합 정보 삭제
			executeQuery('alliance.deleteAllSites');
			executeQuery('alliance.deleteAllMessages');
			executeQuery('alliance.deleteAllMembers');
			executeQuery('alliance.deleteAllAuths');

			$this->setMessage('succeed_a_leaved');
		}

		/**
		 * @brief 연합 해체
		 */
		function procAllianceAdminBreakup($alliance_info = null) {
			@set_time_limit(0);

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			// 연합 정보를 구함
			if(!$alliance_info) $alliance_info = $oAllianceModel->getAllianceInfo();

			// 연합 관리자 권한이 있는지 판별
			if(!$oAllianceModel->isGranted($alliance_info)) return new Object(-1, 'msg_not_permitted');

			// 등록된 사이트 목록을 구함
			$site_list = $oAllianceModel->getSites();

			// 각 사이트에 해체 통보를 보냄
			if(count($site_list)) {
				foreach($site_list as $key => $val) $oAllianceController->sendRequest('breakup', $val->domain);
			}

			// document의 controller class
			$oDocumentController = &getController('document');

			// 등록된 콘텐츠를 500개 단위로 끊어서 삭제
			$page = 1;
			do {
				$args->page = $page;
				$output = executeQueryArray('alliance.getAllContents', $args);
				if(!$output->toBool()) return $output;

				if($output->data) {
					$document_srls = array();

					foreach($output->data as $key => $val) {
						if(!$document_srl) continue;
						if($val->site_srl < 1) {
							$document_srls[] = $document_srl;
							continue;
						}
						$document_srl = $val->target_srl;
						$document_srls[] = $document_srl;
						$output = $oDocumentController->deleteDocument($document_srl, true);
						if(!$output->toBool()) return $output;
					}

					if(count($document_srls)) {
						$document_srls = implode(',', $document_srls);

						$args->document_srls = $document_srls;
						$output = executeQueryArray('alliance.deleteContents', $args);
						if(!$output->toBool()) return $output;
					} else {
						if(!$output->page_navigation->getNextPage()) break;
					}
				}

				$page++;
			} while($output->data && $output->page_navigation->getNextPage());

			// 연합 정보 삭제
			$oAllianceController->deleteAllianceInfo();

			// 등록된 모든 연합 정보 삭제
			executeQuery('alliance.deleteAllSites');
			executeQuery('alliance.deleteAllMessages');
			executeQuery('alliance.deleteAllMembers');
			executeQuery('alliance.deleteAllAuths');

			$this->setMessage('succeed_breakup');
		}

		/**
		 * @brief 연합 추방
		 */
		function procAllianceAdminKick() {
			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 연합 정보를 구함
			$alliance_info = $oAllianceModel->getAllianceInfo();

			// 연합 관리자 권한이 있는지 판별
			if(!$oAllianceModel->isGranted($alliance_info)) return new Object(-1, 'msg_not_permitted');

			// 요청받은 사이트 번호를 구함
			$site_srl = (int)Context::get('site_srl');
			if(!$site_srl) return new Object(-1, 'msg_invalid_request');

			// 요청받은 사이트의 정보를 구함
			$site_info = $oAllianceModel->getSiteInfoBySiteSrl($site_srl);
			if(!$site_info) return new Object(-1, 'msg_invalid_request');

			$domain = $site_info->domain;

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			// 해당 사이트에 추방 통보를 보냄
			$oAllianceController->sendRequest('kick', $domain);

			// document의 controller class
			$oDocumentController = &getController('document');

			// 등록된 콘텐츠를 500개 단위로 끊어서 삭제
			$page = 1;
			do {
				// 해당 사이트의 모든 컨텐츠를 구함
				$args->site_srl = $site_info->site_srl;
				$args->page = $page;
				$output = executeQueryArray('alliance.getAllContents', $args);
				if(!$output->toBool()) return $output;

				// 등록된 컨텐츠가 있다면 삭제 시도
				if($output->data) {
					$document_srls = array();

					foreach($output->data as $key => $val) {
						$document_srl = $val->target_srl;
						$document_srls[] = $document_srl;

						// 문서 삭제
						$output = $oDocumentController->deleteDocument($document_srl, true);
						if(!$output->toBool()) return $output;
					}

					// 삭제한 문서가 있다면
					if(count($document_srls)) {
						$document_srls = implode(',', $document_srls);

						$args->document_srls = $document_srls;

						// 콘텐츠 DB에서 삭제
						$output = executeQueryArray('alliance.deleteContents', $args);
						if(!$output->toBool()) return $output;
					} else {
						// 더 이상 삭제할 것이 없다면 중단
						if(!$output->page_navigation->getNextPage()) break;
					}
				}

				$page++;
			} while($output->data && $output->page_navigation->getNextPage());

			// 삭제
			$oAllianceController->deleteSite($site_info->site_srl);	
		}

		/**
		 * @brief 회원 정보 일괄 동기화
		 */
		function procAllianceAdminBatchMemberSync() {
			$output = executeQueryArray('alliance.getSyncTargetMembers');
			if(!$output->toBool()) return $output;
			if(!$output->data) return new Object(-1, '동기화 할 회원 정보가 없습니다.');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 동기화할 수 있는 사이트를 구함
			$site_args->s_status = 'accepted';
			$site_list = $oAllianceModel->getSites($site_args);

			if(!count($site_list)) return new Object(-1, '동기화 할 수 있는 사이트가 없습니다.');

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			// JSON class
			$oAllianceController->loadJSON($json);

			$failed = 0;
			foreach($site_list as $key => $val) {
				foreach($output->data as $k => $member_info) {
					// 피라미터
					$params['ui'] = $this->_encode($member_info->user_id);
					$params['pp'] = $this->_encode($member_info->password);
					$params['ea'] = $this->_encode($member_info->email_address);
					$params['un'] = $this->_encode($member_info->user_name);
					$params['nn'] = $this->_encode($member_info->nick_name);

					// 동기화 요청
					$buff = $oAllianceController->sendRequest('syncMember', $val->domain, $params);
					if(!$buff) continue;

					if($a_sync[$member_info->member_srl]) continue;
					$output = $json->decode($buff);
					if(!$output) continue;

					if($output->message != 'success') {
						$failed++;
						continue;
					}

					// 회원 DB에 등록
					$oAllianceController->insertMember($member_info, $val->site_srl);

					// 인증 DB에 등록
					$oAllianceController->insertMemberAuth($member_info, $val->site_srl);

					$a_sync[$member_info->member_srl] = true;
				}
			}

			$msg_code = sprintf(Context::getLang('succeed_member_sync'), $failed);

			$this->setMessage($msg_code);
		}

		/**
		 * @brief 콘텐츠 일괄 동기화
		 */
		function procAllianceAdminBatchContentSync() {
			$output = executeQueryArray('alliance.getSyncTargetContents');
			if(!$output->toBool()) return $output;
			if(!$output->data) return new Object(-1, '동기화 할 콘텐츠가 없습니다.');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 설정을 구함
			$config = $oAllianceModel->getModuleConfig();

			if($config->sync->contents_sync != 'Y') return new Object(-1, '콘텐츠 동기화 보내기가 활성화 되어 있지 않습니다.');

			// 대상 모듈 정리
			$target_module_srl = array();
			if($config->sync->target_module_srl) $target_module_srl = explode(',', $config->sync->target_module_srl);

			// 동기화할 수 있는 사이트를 구함
			$site_args->s_status = 'accepted';
			$site_list = $oAllianceModel->getSites($site_args);

			if(!count($site_list)) return new Object(-1, '동기화 할 수 있는 사이트가 없습니다.');

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			// JSON class
			$oAllianceController->loadJSON($json);

			// module의 model class
			$oModuleModel = &getModel('module');

			$failed = 0;
			foreach($site_list as $key => $val) {
				foreach($output->data as $k => $content) {
					$module_srl = $content->module_srl;

					// 대상 모듈에 포함되어 있는지 확인
					switch($config->sync->target) {
						case 'include':
							if(!in_array($module_srl, $target_module_srl)) continue;
							break;
						case 'exclude':
							if(in_array($module_srl, $target_module_srl)) continue;
							break;
					}

					if(!$module[$module_srl]) {
						$module[$module_srl] = $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
					}

					$mid = $this->_encode($module[$module_srl]->mid);

					// 피라미터
					$params['md'] = $mid;
					$params['tt'] = $this->_encode($content->title);
					$params['cc'] = $this->_encode($content->content);
					$params['pp'] = $this->_encode($content->password);
					$params['ui'] = $this->_encode($content->user_id);
					$params['un'] = $this->_encode($content->user_name);
					$params['nn'] = $this->_encode($content->nick_name);
					$params['tg'] = $this->_encode($content->tags);
					$params['ia'] = $this->_encode($content->ipaddress);
					$params['ci'] = md5(getMicroTime());
					$params['call_by_alliancexe'] = 1;

					// 동기화 요청
					$buff = $oAllianceController->sendRequest('syncDocument', $val->domain, $params);
					if(!$buff) continue;

					if($a_sync[$member_info->member_srl]) continue;
					$output = $json->decode($buff);
					if(!$output) continue;

					if($output->message != 'success') {
						$failed++;
						continue;
					}

					// 회원 DB에 등록
					$oAllianceController->insertMember($member_info, 0);
					$oAllianceController->insertMember($member_info, $val->site_srl);

					// 인증 DB에 등록
					$oAllianceController->insertMemberAuth($member_info, $val->site_srl);

					$a_sync[$member_info->member_srl] = true;
				}
			}

			$msg_code = sprintf(Context::getLang('succeed_member_sync'), $failed);

			$this->setMessage($msg_code);
		}
	}
?>