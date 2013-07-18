<?php
	/**
	 * @class  alliance
	 * @author SMaker (dowon2308@paran.com)
	 * @brief  aliianceXE 모듈의 high class
	 **/

	define('AXE_VERSION', '1.2.2');
	define('AXE_USER_AGENT', 'XpressEngine allianceXE module '.AXE_VERSION);
	define('AXE_CONTENT_TYPE', 'application/json');

	class alliance extends ModuleObject {

		var $add_triggers = array(
			array('moduleHandler.init', 'alliance', 'controller', 'triggerAddMemberMenu', 'after'),
			array('document.insertDocument', 'alliance', 'controller', 'triggerInsertDocument', 'after'),
			array('document.updateDocument', 'alliance', 'controller', 'triggerUpdateDocument', 'after'),
			array('document.deleteDocument', 'alliance', 'controller', 'triggerDeleteDocument', 'after'),
			array('member.doLogin', 'alliance', 'controller', 'triggerAfterLogin', 'after'),
			array('member.insertMember', 'alliance', 'controller', 'triggerAfterSignUp', 'after'),
			array('member.deleteMember', 'alliance', 'controller', 'triggerDeleteMember', 'after')
		);

		/**
		 * @brief allianceXE constructor
		 */
		function alliance() {
			// api methods
			$this->api_methods = array('invite', 'accept', 'refuse', 'syncDocument', 'syncMember', 'syncLogin', 'syncSite', 'auth', 'unauth', 'leaveMember', 'breakup', 'leave', 'kick', 'deleteDocument', 'updateDocument', 'getComments', 'join', 'test');

			// current_url
			$this->current_url = $this->getCurrentUrl();

			// SSL 사용시 인증/인증해제 등 개인정보와 관련된 action에 대해 SSL 전송하도록 지정
			if(Context::get('_use_ssl') == 'optional') {
				Context::addSSLAction('dispAllianceAuth');
				Context::addSSLAction('dispAllianceUnauth');
				Context::addSSLAction('dispAllianceJoin');
				Context::addSSLAction('dispAllianceLeave');
				Context::addSSLAction('procAllianceAuth');
				Context::addSSLAction('procAllianceUnauth');
				Context::addSSLAction('procAllianceJoin');
			}
		}

		/**
		 * @brief 설치시 추가 작업이 필요할시 구현
		 **/
		function moduleInstall() {
			$oModuleController = &getController('module');

			// 트리거 일괄 추가
			foreach($this->add_triggers as $trigger) {
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		/**
		 * @brief 설치가 이상이 없는지 체크하는 method
		 **/
		function checkUpdate() {
			$oModuleModel = &getModel('module');
			$oDB = &DB::getInstance();

			// 트리거 일괄 체크
			foreach($this->add_triggers as $trigger) {
				if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
			}

			if(!$oDB->isColumnExists('alliance_sites', 'title')) return true;
			if(!$oDB->isColumnExists('alliance_logs', 'type')) return true;
			if(!$oDB->isColumnExists('alliance_contents', 'site_srl')) return true;
			if(!$oDB->isColumnExists('alliance_messages', 'title')) return true;

			/* 2011.03.12 - 연합 정보 테이블의 count 칼럼 제거 */
			if($oDB->isColumnExists('alliance_info', 'count')) return true;

			return false;
		}

		/**
		 * @brief 업데이트 실행
		 **/
		function moduleUpdate() {
			$oModuleModel = &getModel('module');
			$oModuleController = &getController('module');
			$oDB = &DB::getInstance();

			// 트리거 일괄 추가
			foreach($this->add_triggers as $trigger) {
				if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
					$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}

			if(!$oDB->isColumnExists('alliance_sites', 'title')) {
				$oDB->addColumn('alliance_sites', 'title', 'varchar', 250, '', true);
				$oDB->addIndex('alliance_sites', 'unique_title', 'title', true);
			}

			if(!$oDB->isColumnExists('alliance_logs', 'type'))
				$oDB->addColumn('alliance_logs', 'type', 'char', 20, '', true);

			if(!$oDB->isColumnExists('alliance_contents', 'site_srl')) {
				$oDB->addColumn('alliance_contents', 'site_srl', 'number', 11, 0, true);
				$oDB->addIndex('alliance_contents', 'idx_site_srl', 'site_srl');
			}

			if(!$oDB->isColumnExists('alliance_messages', 'title'))
				$oDB->addColumn('alliance_messages', 'title', 'varchar', 250, '', true);

			/* 2011.03.12 - 연합 정보 테이블의 count 칼럼 제거 */
			if($oDB->isColumnExists('alliance_info', 'count'))
				$oDB->dropColumn('alliance_info', 'count');

			return new Object(0, 'success_updated');
		}

		/**
		 * @bvrief 모듈 제거
		 */
		function moduleUninstall() {
			// allianceXE의 model class
			$oAllianceModel = &getModel('alliance');

			// 연합 정보를 구함
			$alliance_info = $oAllianceModel->getAllianceInfo();

			// 연합 정보가 있다면 처리
			if($alliance_info) {
				// allianceXE의 admin controller class
				$oAllianceAdminController = &getAdminController('alliance');

				// 연합 관리자일 경우 연합 해체
				if($oAllianceModel->isGranted($alliance_info)) {
					$oAllianceAdminController->procAllianceAdminBreakup($alliance_info);
				} else {
					// 아니면 탈퇴 처리
					$oAllianceAdminController->procAllianceAdminLeave($alliance_info);
				}
			}

			return new Object();
		}

		/**
		 * @brief 캐시 파일 재생성
		 **/
		function recompileCache() {
		}

		/**
		 * @brief 도메인에 http:// 프로토콜을 추가
		 */
		function arrangeDomain($domain) {
			if(!preg_match('/^[a-z]+:\/\//i', $domain)) $domain = 'http://'.$domain;
			if(substr($domain, -1) == '/') $domain = substr_replace($domain, '', -1);

			return $domain;
		}

		/**
		 * @brief 도메인에서 http://등의 프로토콜을 제거
		 */
		function cleanDomain($domain) {
			$domain = preg_replace('/[a-z]+:\/\//i', '', $domain);
			if(substr($domain, -1) == '/') $domain = substr_replace($domain, '', -1);

			return $domain;
		}

		/**
		 * @brief 현재 URL에서 http://등의 프로토콜을 제거하여 반환
		 */
		function getCurrentUrl() {
			return $this->cleanDomain(Context::getRequestUri());
		}

		/**
		 * @brief 암호화 함수
		 */
		function _encode($var) {
			return base64_encode(base64_encode(serialize($var)));
		}

		/**
		 * @brief 복호화 함수
		 */
		function _decode($var) {
			return unserialize(base64_decode(base64_decode($var)));
		}

		/**
		 * @brief 지정된 변수의 값이 값 목록에 포함되지 않으면 기본값을 지정
		 */
		function _filter(&$var, $values, $default) {
			if(!in_array($var, $values)) $var = $default;
		}

		/**
		 * @brief 지정된 배열의 요소들의 값이 값 목록에 포함되지 않으면 기본값을 지정
		 */
		function _filterArray(&$var, $values, $default) {
			foreach($var as $key => $val) if(!in_array($val, $value)) $var[$key] = $default;
		}

		/**
		 * @brief 변수가 비어있다면 기본값을 지정
		 * @param[in] $var 변수
		 * @param[in] $value 기본값
		 */
		function _setDefaultValue(&$var, $value, $cond = NULL, $strict = false) {
			if($strict && empty($var)) {
				if($cond && $var != $cond || !isset($cond)) $var = $value;
			}
			if(!$strict && !$var) $var = $value;
		}

		/**
		 * @brief API 실행 중단
		 * @parma[in] $message 에러 메시지
		 * @return none
		 */
		function stopAPI($message) {
			$this->setError(-1);
			$this->setMessage($message);
			return new Object(-1, $message);
		}
	}
?>