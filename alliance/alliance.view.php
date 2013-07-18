<?php
	/**
	 * @class  allianceView
	 * @author SMaker (dowon2308@paran.com)
	 * @brief  aliianceXE 모듈의 view class
	 **/

	class allianceView extends alliance {

		/**
		 * @brief 초기화
		 */
		function init() {
			// 로그인 상태가 아닐 경우 에러
			if(!Context::get('is_logged')) return new Object(-1, 'msg_not_permitted');

			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 모듈 설정과 사이트 목록을 구함
			$this->config = $oAllianceModel->getModuleConfig(true);

			// 기본 스킨 지정
			$this->_setDefaultValue($this->config->skin->skin, 'default');

			$site_list = $this->config->sites;
			$skin_info = unserialize($this->config->skin->extra_vars);

			$this->site_list = $site_list;

			unset($this->config->sites);
			unset($this->config->extra_vars);

			Context::set('config', $this->config);
			Context::set('skin_info', $skin_info);
			Context::set('site_list', $site_list);
			$skin = $this->config->skin->skin;

			// template path 지정
			$tpl_path = sprintf('%sskins/%s', $this->module_path, $skin);
			if(!is_dir($tpl_path)) $tpl_path = sprintf('%sskins/%s', $this->module_path, 'default');

			$this->setTemplatePath($tpl_path);
		}

		/**
		 * @brief 연합 사이트 동기화 관리
		 */
		function dispAllianceManageSync() {
			// 로그인 정보를 구함
			$logged_info = Context::get('logged_info');

			// 인증 정보를 구함
			$args->member_srl = $logged_info->member_srl;
			$output = executeQueryArray('alliance.getMembers', $args);
			if($output->data) {
				foreach($output->data as $key => $val) {
					$join[$val->site_srl] = true;
				}
			}

			// 인증 정보를 구함
			$args->member_srl = $logged_info->member_srl;
			$output = executeQueryArray('alliance.getMemberAuths', $args);
			if($output->data) {
				foreach($output->data as $key => $val) {
					$data[$val->site_srl] = true;
				}
			}

			Context::set('auth_status', $data);
			Context::set('join_status', $join);

			$this->setTemplateFile('manage_sync');
		}

		/**
		 * @brief 인증
		 */
		function dispAllianceAuth() {
			$site_srl = Context::get('site_srl');
			if(!$site_srl) return new Object(-1, 'msg_invalid_request');

			if(count($this->site_list)) {
				$isValid = false;
				foreach($this->site_list as $key => $val) {
					if($isValid) break;
					if($val->site_srl != $site_srl) continue;
					if($val->site_srl == $site_srl) {
						$site_info->title = $val->title;
						$site_info->domain = $val->domain;
						Context::set('site_info', $site_info);
					}
					$isValid = true;
				}
			}

			if(!$isValid) return new Object(-1, 'msg_invalid_request');

			$logged_info = Context::get('logged_info');
			$args->site_srl = $site_srl;
			$args->member_srl = $logged_info->member_srl;
			$output = executeQuery('alliance.getMemberAuth', $args);
			if($output->data) return new Object(-1, 'msg_already_authed');

			$this->setTemplateFile('auth');
		}

		/**
		 * @brief 인증 해제
		 */
		function dispAllianceUnauth() {
			$site_srl = Context::get('site_srl');
			if(!$site_srl) return new Object(-1, 'msg_invalid_request');

			if(count($this->site_list)) {
				$isValid = false;
				foreach($this->site_list as $key => $val) {
					if($isValid) break;
					if($val->site_srl != $site_srl) continue;
					if($val->site_srl == $site_srl) {
						$site_info->title = $val->title;
						$site_info->domain = $val->domain;
						Context::set('site_info', $site_info);
					}
					$isValid = true;
				}
			}

			if(!$isValid) return new Object(-1, 'msg_invalid_request');

			$logged_info = Context::get('logged_info');
			$args->site_srl = $site_srl;
			$args->member_srl = $logged_info->member_srl;
			$output = executeQuery('alliance.getMemberAuth', $args);
			if(!$output->data) return new Object(-1, 'msg_already_unauthed');

			$this->setTemplateFile('unauth');
		}

		/**
		 * @brief 가입
		 */
		function dispAllianceJoin() {
			$site_srl = Context::get('site_srl');
			if(!$site_srl) return new Object(-1, 'msg_invalid_request');

			if(count($this->site_list)) {
				$isValid = false;
				foreach($this->site_list as $key => $val) {
					if($isValid) break;
					if($val->site_srl != $site_srl) continue;
					if($val->site_srl == $site_srl) {
						$site_info->title = $val->title;
						$site_info->domain = $val->domain;
						Context::set('site_info', $site_info);
					}
					$isValid = true;
				}
			}

			if(!$isValid) return new Object(-1, 'msg_invalid_request');

			$logged_info = Context::get('logged_info');
			$args->site_srl = $site_srl;
			$args->member_srl = $logged_info->member_srl;
			$output = executeQuery('alliance.getMemberAuth', $args);
			if($output->data) return new Object(-1, '이미 인증이 되어 있습니다.');

			$this->setTemplateFile('join');
		}

		/**
		 * @brief 탈퇴
		 */
		function dispAllianceLeave() {
			$site_srl = Context::get('site_srl');
			if(!$site_srl) return new Object(-1, 'msg_invalid_request');

			if(count($this->site_list)) {
				$isValid = false;
				foreach($this->site_list as $key => $val) {
					if($isValid) break;
					if($val->site_srl != $site_srl) continue;
					if($val->site_srl == $site_srl) {
						$site_info->title = $val->title;
						$site_info->domain = $val->domain;
						Context::set('site_info', $site_info);
					}
					$isValid = true;
				}
			}

			if(!$isValid) return new Object(-1, 'msg_invalid_request');

			$logged_info = Context::get('logged_info');
			$args->site_srl = $site_srl;
			$args->member_srl = $logged_info->member_srl;
			$output = executeQuery('alliance.getMemberAuth', $args);
			if(!$output->data) return new Object(-1, '인증이 되어 있지 않습니다.');

			$this->setTemplateFile('leave');
		}
	}
?>