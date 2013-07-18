<?php
	/**
	 * @class  allianceController
	 * @author SMaker (dowon2308@paran.com)
	 * @brief  aliianceXE 모듈의 controller service class
	 **/

	class allianceControllerService extends alliance {

		/**
		 * @brief 회원 인증
		 */
		function procAllianceAuth() {
			$user_id = Context::get('user_id');
			$password = Context::get('password');
			$site_srl = Context::get('site_srl');

			// 필요한 정보가 넘어오지 않았다면 에러
			if(!$user_id || !$password || !$site_srl) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 사이트 정보를 구함
			$site_info = $oAllianceModel->getSiteInfoBySiteSrl($site_srl);
			if(!$site_info) return new Object(-1, 'msg_invalid_request');

			// 피라미터
			$params['ui'] = $this->_encode($user_id);
			$params['pp'] = $this->_encode(md5($password));

			// 인증 확인 요청 보냄
			$buff = $this->sendRequest('auth', $site_info->domain, $params);
			if(!$buff) return new Object(-1, 'msg_cannot_auth');

			// JSON class
			$this->loadJSON($json);

			// JSON 디코딩
			$output = $json->decode($buff);

			// 성공하지 못했다면 에러 메시지 출력
			if($output->message != 'success') return new Object(-1, $output->message);

			// 로그인 정보를 구함
			$logged_info = Context::get('logged_info');

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			// 회원 DB에 등록
			$oAllianceController->insertMember($logged_info, $site_srl);

			// 인증 DB에 등록
			$member_info->member_srl = $logged_info->member_srl;
			$member_info->user_id = $user_id;
			$member_info->password = $password;
			$output = $oAllianceController->insertMemberAuth($member_info, $site_srl);
			if(!$output->toBool()) return new Object(-1, "인증에 실패하였습니다.\n\n관리자에게 문의하세요.");

			// 메시지 지정
			$this->setMessage('succeed_authed');
		}

		/**
		 * @brief 회원 인증 해제
		 */
		function procAllianceUnauth() {
			$user_id = Context::get('user_id');
			$password = Context::get('password');
			$site_srl = Context::get('site_srl');

			// 필요한 정보가 넘어오지 않았다면 에러
			if(!$user_id || !$password || !$site_srl) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 사이트 정보를 구함
			$site_info = $oAllianceModel->getSiteInfoBySiteSrl($site_srl);
			if(!$site_info) return new Object(-1, 'msg_invalid_request');

			// 피라미터
			$params['ui'] = $this->_encode($user_id);
			$params['pp'] = $this->_encode(md5($password));

			// 인증 해제 요청 보냄
			$buff = $this->sendRequest('unauth', $site_info->domain, $params);
			if(!$buff) return new Object(-1, 'msg_cannot_disable_auth');

			// JSON class
			$this->loadJSON($json);

			// JSON 디코딩
			$output = $json->decode($buff);

			// 성공하지 못했다면 에러 메시지 출력
			if($output->message != 'success') return new Object(-1, $output->message);

			// 인증 DB에서 삭제
			$logged_info = Context::get('logged_info');
			$auth_args->member_srl = $logged_info->member_srl;
			$auth_args->site_srl = $site_srl;
			$output = executeQuery('alliance.deleteMemberAuth', $auth_args);
			if(!$output->toBool()) return new Object(-1, "인증 해제에 실패하였습니다.\n\n관리자에게 문의하세요.");

			$this->setMessage('succeed_unauthed');
		}

		/**
		 * @brief 간편 가입
		 */
		function procAllianceJoin() {
			$user_id = Context::get('user_id');
			$password = Context::get('password');
			$site_srl = Context::get('site_srl');

			if(!$user_id || !$password || !$site_srl) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 사이트 정보를 구함
			$site_info = $oAllianceModel->getSiteInfoBySiteSrl($site_srl);
			if(!$site_info) return new Object(-1, 'msg_invalid_request');

			// 회원 정보를 구함
			$member_info = Context::get('logged_info');

			// 피라미터
			$params['ui'] = $this->_encode($user_id);
			$params['pp'] = $this->_encode($password);
			$params['ea'] = $this->_encode($member_info->email_address);
			$params['un'] = $this->_encode($member_info->user_name);
			$params['nn'] = $this->_encode($member_info->nick_name);
			$params['ci'] = md5(getMicroTime());

			// 인증 확인 요청 보냄
			$buff = $this->sendRequest('syncMember', $site_info->domain, $params);
			if(!$buff) return new Object(-1, 'msg_cannot_auth');

			// JSON class
			$this->loadJSON($json);

			// JSON 디코딩
			$output = $json->decode($buff);

			// 성공하지 못했다면 에러 메시지 출력
			if($output->message != 'success') return new Object(-1, $output->message);

			// 회원 DB에 등록
			$this->insertMember($member_info, $site_srl);

			// 인증 DB에 등록
			$member_info->member_srl = $member_info->member_srl;
			$member_info->user_id = $user_id;
			$member_info->password = $password;
			$output = $oAllianceController->insertMemberAuth($member_info, $site_srl);
			if(!$output->toBool()) return new Object(-1, "인증에 실패하였습니다.\n\n관리자에게 문의하세요.");

			// 메시지 지정
			$this->setMessage('succeed_authed');
		}

		/**
		 * @brief 간편 탈퇴
		 */
		function procAllianceLeave() {
			$user_id = Context::get('user_id');
			$password = Context::get('password');
			$site_srl = Context::get('site_srl');

			if(!$user_id || !$password || !$site_srl) return new Object(-1, 'msg_invalid_request');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 사이트 정보를 구함
			$site_info = $oAllianceModel->getSiteInfoBySiteSrl($site_srl);
			if(!$site_info) return new Object(-1, 'msg_invalid_request');

			// 피라미터
			$params['ui'] = $this->_encode($user_id);
			$params['pp'] = $this->_encode($password);

			// 인증 확인 요청 보냄
			$buff = $this->sendRequest('leaveMember', $site_info->domain, $params);
			if(!$buff) return new Object(-1, 'msg_cannot_a_leave');

			// JSON class
			$this->loadJSON($json);

			// JSON 디코딩
			$output = $json->decode($buff);

			// 성공하지 못했다면 에러 메시지 출력
			if($output->message != 'success') return new Object(-1, $output->message);

			// 회원 DB에서 삭제
			$logged_info = Context::get('logged_info');
			$args->site_srl = $site_srl;
			$args->target_srl = $logged_info->member_srl;
			$output = executeQuery('alliance.deleteMember', $args);

			// 인증 DB에서 삭제
			$auth_args->member_srl = $logged_info->member_srl;
			$auth_args->site_srl = $site_srl;
			$output = executeQuery('alliance.deleteMemberAuth', $auth_args);

			// 메시지 지정
			$this->setMessage('succeed_authed');
		}
	}
?>