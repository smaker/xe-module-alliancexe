<?php
	/**
	 * @class  allianceControllerAPI
	 * @author SMaker (dowon2308@paran.com)
	 * @brief  aliianceXE 모듈의 controller api class
	 **/

	// load Service Class
	require_once _XE_PATH_.'modules/alliance/alliance.controller.service.php';

	if (!class_exists("Services_JSON_SocialXE") && !class_exists("Services_JSON_allianceXE")){
		require_once(_XE_PATH_.'modules/alliance/JSON.php');
	}

	class allianceControllerAPI extends allianceControllerService {

		/**
		 * @brief allianceXE API 처리
		 */
		function procAllianceAPI() {
			$type = Context::get('type');
			$this->procAPI($type);
		}

		/**
		 * @brief API 실행
		 */
		function procAPI($type) {
			// 결과 출력을 JSON으로 지정
			Context::setRequestMethod('JSON');

			// 요청받은 값
			$args = Context::getRequestVars();

			// API 종류에 따른 처리
			if(in_array($type, $this->api_methods)) {
				$output = $this->$type($args);
			} else {
				return new Object(-1, 'invalid_api');
			}

			$this->setError($output->getError());
			$this->setMessage($output->getMessage());
			$this->adds($output->getObjectVars());
			return $this;
		}

		/**
		 * @brief 대상 사이트에 요청을 보냄
		 */
		function sendRequest($type, $target_domain, $params = array(), $timeout = 30) {
			if(!in_array($type, $this->api_methods)) return;

			// 기본적으로 전송되어야 할 json data
			$params['module'] = 'alliance';
			$params['act'] = 'procAllianceAPI';
			$params['type'] = $type;
			$params['domain'] = $this->current_url;

			foreach($params as $key => $val) $param[] = $key.'='.$val;

			$params_str = implode('&', $param);

			$header['User-Agent'] = AXE_USER_AGENT;

			$url = $this->arrangeDomain($target_domain);

			// XE에 내장된 FileHandler::getRemoteResource()를 이용함
			return FileHandler::getRemoteResource($url, $params_str, $timeout, 'POST', AXE_CONTENT_TYPE, $header);
		}

		/**
		 * @brief 초대
		 */
		function invite($args) {
			$domain = trim($args->domain);

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 이미 등록된 도메인이면 에러
			if($oAllianceModel->isExistsDomain($domain)) return new Object(-1, 'msg_already_joined_alliance_domain');

			// 연합 정보를 구함
			$alliance_info = $oAllianceModel->getAllianceInfo();

			// 이미 연합에 소속되어 있다면 에러
			if($alliance_info) return new Object(-1, 'msg_cannot_invite_me');

			// 트랜젝션 시작
			$oDB = &DB::getInstance();
			$oDB->begin();

			// 사이트 등록
			$output = $this->insertParentSite($domain, $domain);

			// 사이트 등록에 실패하면 에러
			if(!$output->toBool()) {
				$oDB->rollback();
				return $output;
			}

			// 초대 메시지 등록
			$message_args->domain = $domain;
			$message_args->type = 'R';
			$message_args->title = 'Invitation Message';
			$message_args->content = '#invite';
			$output = $this->insertMessage($message_args);

			// 메시지 등록에 실패하면 에러
			if(!$output->toBool()) {
				$oDB->rollback();
				return $output;
			}

			// 커밋 (commit)
			$oDB->commit();

			return new Object();
		}

		/**
		 * @brief 수락
		 */
		function accept($args) {
			$domain = trim($args->domain);

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 등록된 도메인이 아니면 에러
			if(!$oAllianceModel->isExistsDomain($domain)) return new Object(-1, 'msg_not_exists_alliance_domain');

			// 트랜젝션 시작
			$oDB = &DB::getInstance();
			$oDB->begin();

			// 수락 메시지 등록
			$message_args->domain = $domain;
			$message_args->type = 'R';
			$message_args->content = '#accept';
			$output = $this->insertMessage($message_args);

			// 수락 상태로 변경
			$output = $this->acceptSite($domain);

			// 성공 시 커밋
			if($output->toBool()) {
				$oDB->commit();
			} else {
				// 메시지 등록에 실패하면 에러
				$oDB->rollback();
				return $output;
			}

			// 사이트 목록을 구함
			$site_args->s_status = 'accepted';
			$site_list = $oAllianceModel->getSites($site_args);

			// 사이트 등록 요청
			$params['target_domain'] = $domain;
			$params['target_title'] = $domain;
			if(count($site_list)) {
				foreach($site_list as $key => $val) {
					if($val->domain == $domain) continue;
					$this->sendRequest('syncSite', $val->domain, $params);
				}
			}

			// add
			$this->add('site_list', $site_list);

			return new Object();
		}

		/**
		 * @brief 거절
		 */
		function refuse($args) {
			$domain = trim($args->domain);

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 이미 등록된 도메인이면 에러
			if($oAllianceModel->isExistsDomain($domain)) return new Object(-1, 'msg_exists_alliance_domain');

			// 트랜젝션 시작
			$oDB = &DB::getInstance();
			$oDB->begin();

			// 거절 메시지 등록
			$message_args->domain = $domain;
			$message_args->type = 'R';
			$message_args->content = '#refuse';
			$output = $this->insertMessage($message_args);

			// 수락 상태로 변경
			$output = $this->refuseSite($domain);

			// 성공 시 커밋
			if($output->toBool()) {
				$oDB->commit();
			} else {
				// 메시지 등록에 실패하면 에러
				$oDB->rollback();
				return $output;
			}

			return new Object();
		}

		/**
		 * @brief 연합 메시지
		 */
		function message($args) {
			$domain = trim($args->domain);
			$reference_domain = trim($args->reference_domain);
			$title = trim($args->title);
			$content = trim($args->content);
			if(!$domain || !$title || !$content) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 등록되지 않은 도메인이면 에러
			if(!$oAllianceModel->isExistsDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 참조 도메인이 넘어왔다면 검사
			if($reference_domain) {
				// 참조 도메인의 유효성 검사
				if(!$oAllianceModel->isValidDomain($reference_domain)) return new Object(-1, 'msg_not_authorized_access');

				// 등록되지 않은 도메인이면 에러
				if(!$oAllianceModel->isExistsDomain($reference_domain)) return new Object(-1, 'msg_not_authorized_access');
			}

			// 메시지 수신
			$message_args->domain = $reference_domain ? $this->cleanDomain($reference_domain) : $this->cleanDomain($domain);
			$message_args->title = $title;
			$message_args->content = $content;
			$message_args->type = 'R';
			$output = $this->insertMessage($message_args);

			// 메시지 수신에 실패할 경우 에러
			if(!$output->toBool()) return new Object(-1, 'msg_failed_receive_message');

			// 연합 정보를 구함
			$alliance_info = $oAllianceModel->getAllianceInfo();

			// 관리자라면 연합에 소속된 다른 사이트에도 메시지를 보냄
			$site_args->s_status = 'accepted';
			$site_list = $oAllianceModel->getSites($site_args);
			if(count($site_list)) {
				foreach($site_list as $key => $val) {
					$params['title'] = $title;
					$params['content'] = $content;
			
					$this->sendRequest('message', $this->current_url, $params);
				}
			}

			return new Object();
		}

		/**
		 * @brief 연합 정보 동기화
		 */
		function syncConfig($args) {
			$config = $this->_decode($args->ut);
			if(!is_object($config)) return new Object(-1, 'msg_invalid_request');

			// 도메인을 구함
			$domain = Context::get('domain');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 동기화 시도
			$update_args->parent_domain = $domain;
			$update_args->title = $config->alliance_info->title;
			$update_args->count = $config->alliance_info->count;
			$output = $this->updateAllianceInfo($update_args);

			if(!$output->toBool()) return $output;

			return new Object();
		}

		/**
		 * @brief 콘텐츠 동기화
		 */
		function syncDocument($args) {
			$mid = $this->_decode($args->md);
			$title = $this->_decode($args->tt);
			$content = $this->_decode($args->cc);
			$password = $this->_decode($args->pp);
			$user_id = $this->_decode($args->ui);
			$user_name = $this->_decode($args->un);
			$nick_name = $this->_decode($args->nn);
			$tags = $this->_decode($args->tg);
			$ipaddress = $this->_decode($args->ia);
			$cid = $args->ci;
			$call_by_alliancexe = $args->call_by_alliancexe;
			$domain = $args->domain;

			if(!$mid) return new Object(-1, 'msg_invalid_request');
			if(!$title) return new Object(-1, 'msg_invalid_request');
			if(!$content) return new Object(-1, 'msg_invalid_request');
			if(!$nick_name) return new Object(-1, 'msg_invalid_request');
			if(!$ipaddress) return new Object(-1, 'msg_invalid_request');
			if(!$cid) return new Object(-1, 'msg_invalid_request');
			if(!$call_by_alliancexe) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 사이트 정보를 구함
			$site_info = $oAllianceModel->getSiteInfoByDomain($domain);
			if(!$site_info) return new Object(-1, 'msg_not_authorized_access');

			// 모듈 설정을 구함
			$config = $oAllianceModel->getModuleConfig();

			// 콘텐츠 동기화를 받지 않는다면 중단
			if($config->sync->contents_sync_r != 'Y') return new Object(-1, 'alert_disabled_contents_sync');

			// 비회원 콘텐츠 동기화를 받지 않는다면 중단
			if(!$user_id && $config->sync->guest_sync_r != 'Y') return new Object(-1, 'alert_disabled_guest_sync');

			// 콘텐츠 제목 머릿말이 있다면
			if($config->sync->contents_title_prefix_r == 'site_title') {
				$title = '['.$site_info->title.'] '.$title;
			}

			// 사이트 고유 번호
			$site_srl = $site_info->site_srl;
			// 사용자 ID가 넘어왔다면 인증이 되어 있는지 확인
			if($user_id) {
				$member_info = $oAllianceModel->getMemberInfoByUserId($user_id, $site_srl);
				$user_name = $member_info->user_name;
				$nick_name = $member_info->nick_name;
				$member_srl = $member_info->member_srl;

				// 인증되지 않은 회원이라면 무시
				if(!$member_srl) $pass_sync = true;
			}

			// 동일한 mid의 모듈이 있는지 확인
			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByMid($mid);
			$module_srl = $module_info->module_srl;

			// 동일한 mid의 모듈이 있다면 등록
			if($module_srl) {
				if(!$pass_sync) {
					// document의 controller class
					$oDocumentController = &getController('document');

					$args->title = $title;
					$args->content = $content;
					$args->password = $password;
					$args->user_id = $user_id;
					$args->user_name = $user_name;
					$args->nick_name = $nick_name;
					$args->member_srl  = $member_srl;
					$args->tags = $tags;
					$args->regdate = date('YmdHis');
					$args->password_is_hashed = true;
					$args->module_srl = $module_srl;
					$args->allow_comment = 'Y';
					$args->lock_comment = 'N';
					$args->ipaddress = $ipaddress;
					$args->call_by_alliancexe = true;
					$args->extra_vars->cid = $cid;

					// 문서 등록
					$output = $oDocumentController->insertDocument($args, true);
					if(!$output->toBool()) return $output;

					// 문서 번호를 구함
					$document_srl = $output->get('document_srl');

					// XE Core #19290426에 따른 IP 주소 업데이트
					$update_args->document_srl = $document_srl;
					$update_args->ipaddress = $ipaddress;
					executeQuery('alliance.updateDocumentIpaddress', $update_args);

					// 중복 동기화 되지 않도록 콘텐츠 DB에 등록
					$output = $this->insertContent($site_srl, $document_srl, $cid);
					if(!$output->toBool()) return $output;

					unset($args);
					unset($output);

					$args->domain = $this->cleanDomain($domain);
					$args->last_update = date('YmdHis');
					executeQuery('alliance.updateLastUpdate', $args);
				}
			}

			// 동기화를 하지 않았따면 동기화를 하지 않았다는 것을 알려줌
			if($pass_sync) $this->add('pass_sync', 'Y');

			return new Object();
		}

		/**
		 * @brief 콘텐츠를 동기화 받음
		 */
		function updateDocument($args) {
			$mid = $this->_decode($args->md);
			$title = $this->_decode($args->tt);
			$content = $this->_decode($args->cc);
			$password = $this->_decode($args->pp);
			$tags = $this->_decode($args->tg);
			$ipaddress = $this->_decode($args->ia);
			$cid = $args->ci;
			$call_by_alliancexe = $args->call_by_alliancexe;
			$domain = $args->domain;

			// 필요한 정보가 넘어오지 않았다면 에러
			if(!$mid) return new Object(-1, 'msg_invalid_request');
			if(!$title) return new Object(-1, 'msg_invalid_request');
			if(!$content) return new Object(-1, 'msg_invalid_request');
			if(!$ipaddress) return new Object(-1, 'msg_invalid_request');
			if(!$cid) return new Object(-1, 'msg_invalid_request');
			if(!$call_by_alliancexe) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 사이트 정보를 구함
			$site_info = $oAllianceModel->getSiteInfoByDomain($domain);
			if(!$site_info) return new Object(-1, 'msg_not_authorized_access');

			// 모듈 설정을 구함
			$config = $oAllianceModel->getModuleConfig();

			// 콘텐츠 동기화를 받지 않는다면 중단
			if($config->sync->contents_sync_r != 'Y') return new Object(-1, 'alert_disabled_contents_sync');

			// 콘텐츠 제목 머릿말이 있다면
			if($config->sync->contents_title_prefix_r == 'site_title') {
				$title = '['.$site_info->title.'] '.$title;
			}

			// 사이트 고유 번호
			$site_srl = $site_info->site_srl;

			// 동일한 mid의 모듈이 있는지 확인
			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByMid($mid);
			$module_srl = $module_info->module_srl;

			// 동일한 mid의 모듈이 있다면 수정
			if($module_srl) {
				// 콘텐츠 ID로 문서 번호를 구함
				$document_args->content_id = $cid;
				$output = executeQuery('alliance.getDocumentSrlByContentId', $document_args);
				if(!$output->toBool()) return $output;
				if(!$output->data) return new Object(-1, 'msg_invalid_request');

				$document_srl = $output->data->document_srl;

				// document의 model class
				$oDocumentModel = &getModel('document');

				// 기존의 문서 정보를 구함
				$oDocument = $oDocumentModel->getDocument($document_srl, true, false);
				if(!$oDocument->isExists()) return new Object(-1, 'msg_invalid_request');

				// document의 controller class
				$oDocumentController = &getController('document');

				$update_args->document_srl = $document_srl;
				$update_args->title = $title;
				$update_args->content = $content;
				$update_args->password = $password;
				$update_args->member_srl  = $member_srl;
				$update_args->tags = $tags;
				$update_args->regdate = date('YmdHis');
				$update_args->module_srl = $module_srl;
				$update_args->allow_comment = 'Y';
				$update_args->lock_comment = 'N';
				$update_args->ipaddress = $ipaddress;
				$update_args->call_by_alliancexe = true;
				$update_args->extra_vars->cid = $cid;

				// 문서 수정
				$output = $oDocumentController->updateDocument($oDocument, $update_args);
				if(!$output->toBool()) return $output;

				unset($args);
				unset($output);

				// 최근 동기화 일자 변경
				$args->domain = $this->cleanDomain($domain);
				$args->last_update = date('YmdHis');
				executeQuery('alliance.updateLastUpdate', $args);
			}

			// 동기화를 하지 않았따면 동기화를 하지 않았다는 것을 알려줌
			if($pass_sync) $this->add('pass_sync', 'Y');

			return new Object();
		}

		/**
		 * @brief 회원 정보 동기화
		 */
		function syncMember($args) {
			$user_id = $this->_decode($args->ui);
			$password = $this->_decode($args->pp);
			$email_address = $this->_decode($args->ea);
			$user_name = $this->_decode($args->un);
			$nick_name = $this->_decode($args->nn);
			$domain = $args->domain;

			if(!$user_id) return new Object(-1, 'msg_invalid_request');
			if(!$password) return new Object(-1, 'msg_invalid_request');
			if(!$email_address) return new Object(-1, 'msg_invalid_request');
			if(!$user_name && !$nick_name) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 등록되지 않은 도메인이면 에러
			$site_info = $oAllianceModel->getSiteInfoByDomain($domain);
			if(!$site_info) return new Object(-1, 'msg_not_authorized_access');

			// 모듈 설정을 구함
			$config = $oAllianceModel->getModuleConfig();

			// 콘텐츠 동기화를 받지 않는다면 중단
			if($config->sync->member_sync_r != 'Y') return new Object(-1, 'alert_disabled_member_sync');

			// 트랜젝션 시작
			$oDB = &DB::getInstance();
			$oDB->begin();

			// 이름이 없는 경우 닉네임으로 대체
			if(!$user_name) $user_name = $nick_name;

			// 닉네임이 없는 경우 이름으로 대체
			if(!$nick_name) $nick_name = $user_name;

			// 이름과 닉네임에 사이트 이름 붙임
			$user_name = "[{$site_info->title}] {$user_name}";
			$nick_name = "[{$site_info->title}] {$nick_name}";

			// 회원 가입 처리
			$member_args->user_id = $user_id;
			$member_args->password = $password;
			$member_args->email_address = $email_address;
			$member_args->user_name = $user_name;
			$member_args->nick_name = $nick_name;
			$oMemberController = &getController('member');
			$output = $oMemberController->insertMember($member_args);
			unset($oMemberController);

			// 회원 가입에 실패하면 에러
			if(!$output->toBool()) {
				$oDB->rollback();
				return $output;
			}

			// 회원 고유 ID를 구함
			$mid = md5(getMicroTime());

			$member_srl = $output->get('member_srl');

			// 중복 동기화 되지 않도록 DB에 등록
			$member_info->member_srl = $member_srl;
			$this->insertMember($member_info, $site_info->site_srl);

			// 인증 DB에 등록
			$member_info->user_id = $user_id;
			$member_info->password = $password;
			$this->insertMemberAuth($member_info, $site_info->site_srl);

			// 커밋
			$oDB->commit();

			return new Object();
		}

		function syncSite($args) {
			$domain = $args->domain;
			$target_domain = $args->target_domain;
			$target_title = $args->target_title;

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 등록되지 않은 도메인이면 에러
			if(!$oAllianceModel->isExistsDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			$output = $this->insertFriendSite($target_domain, $target_title, true);
			if(!$output->toBool()) return $output;

			return new Object();
		}

		/**
		 * @brief 인증
		 */
		function auth($args) {
			$domain = $args->domain;
			$user_id = $this->_decode($args->ui);
			$password = $this->_decode($args->pp);
			if(!$user_id || !$password) return new Object(-1, 'msg_not_authorized_access');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 인증
			$args->user_id = $user_id;
			$args->password = $password;
			$output = executeQuery('alliance.chkMember', $args);

			if($output->toBool()) {
				// 일치하는 아이디가 없으면 에러
				if(!$output->data->count) return new Object(-1, 'msg_failed_auth');
			} else {
				// DB 에러로 실패 시 에러
				return new Object(-1, 'msg_cannot_auth');
			}

			return new Object();
		}

		/**
		 * @brief 인증 해제
		 */
		function unauth($args) {
			$domain = $args->domain;
			$user_id = $this->_decode($args->ui);
			$password = $this->_decode($args->pp);

			// 아이디와 비밀번호가 넘어오지 않았다면 에러
			if(!$user_id || !$password) return new Object(-1, 'msg_not_authorized_access');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 아이디와 비밀번호가 일치하는지 확인
			$args->user_id = $user_id;
			$args->password = $password;
			$output = executeQuery('alliance.chkMember', $args);

			if($output->toBool()) {
				// 일치하는 아이디가 없으면 에러
				if(!$output->data->count) return new Object(-1, 'msg_failed_unauth');
			} else {
				// DB 에러로 실패 시 에러
				return new Object(-1, 'msg_cannot_unauth');
			}

			return new Object();
		}

		/** 
		 * @brief 연합 해체
		 **/
		function breakup($args) {
			@set_time_limit(0);

			$domain = $args->domain;
			if(!$domain) return new Object(-1, 'msg_not_authorized_access');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 연합 정보 삭제
			$this->deleteAllianceInfo();

			// document의 controller class
			$oDocumentController = &getController('document');

			// 등록된 콘텐츠를 500개 단위로 끊어서 삭제
			$page = 1;
			do {
				$args->page = $page;
				$output = executeQueryArray('alliance.getAllContents', $args);
				if(!$output->toBool()) return new Object(-1, 'alliance_api_error');

				if($output->data) {
					$document_srls = array();

					foreach($output->data as $key => $val) {
						if(!$document_srl) continue;
						if($val->site_srl < 1) continue;
						$document_srl = $val->target_srl;
						$document_srls[] = $document_srl;
						if(!$output->toBool()) return new Object(-1, 'alliance_api_error');
					}

					if(!count($document_srls)) continue;

					$document_srls = implode(',', $document_srls);

					$args->document_srls = $document_srls;
					$output = executeQueryArray('alliance.deleteContents', $args);
					if(!$output->toBool()) return new Object(-1, 'alliance_api_error');
				}

				$page++;
			} while($output->data && $output->page_navigation->getNextPage());

			executeQuery('alliance.deleteAllSites');
			executeQuery('alliance.deleteAllMessages');
			executeQuery('alliance.deleteAllAuths');

			return new Object();
		}

		/**
		 * @brief 연합 추방
		 */
		function kick($args) {
			$domain = $args->domain;
			if(!$domain) return new Object(-1, 'msg_not_authorized_access');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 연합 정보를 구함
			$alliance_Info = $oAllianceModel->getAllianceInfo();

			// 연합 관리자 도메인과 요청 받은 도메인이 다르다면 에러
			if($alliance_info && $alliance_info->parent_domain != $domain) return new Object(-1, 'msg_not_authorized_access');

			// document의 controller class
			$oDocumentController = &getController('document');

			// 등록된 콘텐츠를 500개 단위로 끊어서 삭제
			$page = 1;
			do {
				$args->page = $page;
				$output = executeQueryArray('alliance.getAllContents', $args);
				if(!$output->toBool()) return new Object(-1, 'alliance_api_error');

				if($output->data) {
					$document_srls = array();

					foreach($output->data as $key => $val) {
						if($val->site_srl < 1) continue;
						$document_srl = $val->target_srl;
						$document_srls[] = $document_srl;
						if(!$output->toBool()) return new Object(-1, 'alliance_api_error');
					}

					if(!count($document_srls)) continue;

					$document_srls = implode(',', $document_srls);

					$args->document_srls = $document_srls;
					$output = executeQueryArray('alliance.deleteContents', $args);
					if(!$output->toBool()) return new Object(-1, 'alliance_api_error');
				}

				$page++;
			} while($output->data && $output->page_navigation->getNextPage());

			executeQuery('alliance.deleteAllSites');
			executeQuery('alliance.deleteAllMessages');
			executeQuery('alliance.deleteAllMembers');
			executeQuery('alliance.deleteAllAuths');
		}

		/**
		 * @brief 콘텐츠 삭제
		 */
		function deleteDocument($args) {
			$domain = $args->domain;
			$content_id = $args->content_id;

			// 콘텐츠 ID가 넘어오지 않았다면 에러
			if(!$content_id) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 콘텐츠 ID로 문서 번호를 구함
			$document_args->content_id = $content_id;
			$output = executeQuery('alliance.getDocumentSrlByContentId', $document_args);
			if(!$output->toBool()) return $output;
			if(!$output->data) return new Object(-1, 'msg_invalid_request');

			$document_srl = $output->data->document_srl;

			// document의 controller class
			$oDocumentController = &getController('document');

			// 문서 삭제
			$output = $oDocumentController->deleteDocument($content_srl, true);
			if(!$output->toBool()) return $output;

			return new Object();
		}

		/**
		 * @brief 연합 탈퇴 처리
		 */
		function leave($args) {
			$domain = $args->domain;

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			$this->deleteSiteByDomain($domain);
		}

		/** 
		 * @brief 댓글 요청 처리
		 */
		function getComments($args) {
			$domain = $args->domain;
			$cid = $args->content_id;

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain, false)) return new Object(-1, 'msg_not_authorized_access');

			// 콘텐츠 ID가 넘어왔는지 확인
			if(!$cid) return new Object(-1, 'msg_not_authorized_access');

			// 콘텐츠가 존재하는지 확인
			if(!$oAllianceModel->isExistsContent($cid)) return new Object(0, 'msg_not_exists_content');

			$comment_args->content_id = $cid;
			$comment_args->sort_index = 'voted_count';
			$comment_args->sort_index2 = 'list_order';
			$comment_args->order_type = 'desc';
			$comment_args->order_type2 = 'asc';

			$output = executeQueryArray('alliance.getComments', $comment_args);
			if(count($output->data)) {
				foreach($output->data as $key => $val) {
					$comments[$key]->author = htmlspecialchars($val->nick_name);
					$comments[$key]->content = cut_str(strip_tags($val->content), 30);
					$comments[$key]->voted = $val->voted_count ? $val->voted_count : 0;
					$comments[$key]->blamed = $val->blamed_count ? $val->blamed_count : 0;
					$comments[$key]->regdate = $val->regdate;
					$comments[$key]->url = getFullUrl('', 'document_srl', $val->document_srl).'#'.$val->comment_srl;
				}
			}

			$this->add('comments', $comments);

			return new Object();
		}

		/**
		 * @brief 연합 자동 가입
		 */
		function join($args) {
			$domain = $args->domain;
			$a_info = $this->_decode($args->a_info);

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 연합 정보를 구함
			$alliance_info = $oAllianceModel->getAllianceInfo();

			// 이미 다른 연합에 소속되어 있으면 에러
			if($alliance_info) return new Object(-1, 'msg_cannot_invite_me');

			// 연합 정보가 넘어오지 않았다면 에러
			if(!$a_info || !is_object($a_info)) return new Object(-1, 'msg_not_authorized_access');

			// 트렌잭션 시작
			$oDB = &DB::getInstance();
			$oDB->begin();

			// 연합 정보 등록
			$output = $this->insertAllianceInfo($a_info);

			// 연합 정보 등록에 실패하면 에러
			if(!$output->toBool()) {
				$oDB->rollback();
				return $output;
			}

			// 사이트 등록
			$output = $this->insertParentSite($domain, $domain, true);

			// 사이트 등록에 실패하면 에러
			if(!$output->toBool()) {
				$oDB->rollback();
				return $output;
			}

			// 다른 사이트가 있다면 추가
			if(count($a_info->site_list)) {
				foreach($a_info->site_list as $key => $val) {
					$title = $val->title;
					$domain = $val->domain;

					// 자기 자신은 추가하지 않음
					if($domain == $this->current_url) continue;

					// 친구 사이트로 추가
					$output = $this->insertFriendSite($domain, $title, true);
					if(!$output->toBool()) {
						$oDB->rollback();
						return $output;
					}
				}
			}

			// 커밋
			$oDB->commit();

			return new Object();
		}

		/**
		 * @brief 간편 탈퇴
		 */
		function leaveMember() {
			$user_id = $this->_decode($args->ui);
			$password = $this->_decode($args->pp);
			$domain = $args->domain;

			if(!$user_id) return new Object(-1, 'msg_invalid_request');
			if(!$password) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 도메인의 유효성 검사
			if(!$oAllianceModel->isValidDomain($domain)) return new Object(-1, 'msg_not_authorized_access');

			// 등록되지 않은 도메인이면 에러
			$site_info = $oAllianceModel->getSiteInfoByDomain($domain);
			if(!$site_info) return new Object(-1, 'msg_not_authorized_access');

			// 인증
			$args->user_id = $user_id;
			$args->password = $password;
			$output = executeQuery('alliance.chkMember', $args);

			if($output->toBool()) {
				// 일치하는 아이디가 없으면 에러
				if(!$output->data->count) return new Object(-1, 'msg_failed_auth');

				// 최고 관리자는 탈퇴 할 수 없음
				if($output->data->is_admin == 'Y') return new Object(-1, 'msg_cannot_leave_adm');
			} else {
				// DB 에러로 실패 시 에러
				return new Object(-1, 'msg_cannot_auth');
			}

			$member_args->user_id = $user_id;
			$member_args->site_srl = $site_info->site_srl;
			$output = executeQuery('alliance.getMemberSrlByUserId', $member_args);
			if(!$output->toBool()) return $output;
			if(!$output->data) return new Object(-1, 'msg_invalid_request');

			$member_srl = $output->data->member_srl;

			// 트랜젝션 시작
			$oDB = &DB::getInstance();
			$oDB->begin();

			// 탈퇴 처리
			$oMemberController = &getController('member');
			$output = $oMemberController->deleteMember($member_srl);
			unset($oMemberController);

			// 탈퇴에 실패하면 롤백
			if(!$output->toBool()) {
				$oDB->rollback();
				return $output;
			}

			// 커밋
			$oDB->commit();

			return new Object();
		}

		/**
		 * @brief 시험용 API (allianceXE 설치 여부 확인용)
		 */
		function test() {
			return new Object();
		}

		/**
		 * @brief load JSON class
		 */
		function loadJSON(&$json) {
			if (class_exists('Services_JSON_SocialXE')) $json = new Services_JSON_SocialXE();
			else $json = new Services_JSON_allianceXE();
		}
	}
?>
