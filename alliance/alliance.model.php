<?php
	/**
	 * @class  allianceController
	 * @author FunnyXE (dowon2308@paran.com)
	 * @brief  aliianceXE 모듈의 model class
	 **/

	class allianceModel extends alliance {

		/**
		 * @brief 초기화
		 */
		function init() {
		}

		/**
		 * @brief 도메인이 존재하는 지 확인
		 */
		function isExistsDomain($domain) {
			if(!$domain) return false;

			$args->domain = $this->cleanDomain($domain);
			$output = executeQuery('alliance.checkSite', $args);
			if($output->data->count) return true;

			return false;
		}

		/**
		 * @brief 등록된 콘텐츠인지 확인
		 */
		function isExistsContent($cid) {
			$args->content_id = $cid;
			$output = executeQuery('alliance.isExistsContent', $args);
			if($output->data->count) return true;

			return false;
		}

		/**
		 * @brief 등록된 모든 사이트를 구함
		 */
		function getSites($obj = null) {
			$args->s_status = $obj->s_status;
			$args->sort_index = $obj->sort_index ? $obj->sort_index : 'list_order';
			$args->order_type = $obj->order_type ? $obj->order_type : 'desc';

			$output = executeQueryArray('alliance.getSites', $args);
			return $output->data;
		}

		/**
		 * @brief 도메인으로 사이트 정보 구함
		 */
		function getSiteInfoByDomain($domain) {
			$args->domain = $domain;
			$output = executeQuery('alliance.getSiteInfoByDomain', $args);
			return $output->data;
		}

		/**
		 * @brief site_srl로 사이트 정보 구함
		 */
		function getSiteInfoBySiteSrl($site_srl) {
			$args->site_srl = $site_srl;
			$output = executeQuery('alliance.getSiteInfo', $args);
			return $output->data;
		}

		/**
		 * @brief 연합을 생성할 수 있는지 확인
		 */
		function isCreatable($alliance_info) {
			return $alliance_info ? false : true;
		}

		/**
		 * @brief allianceXE의 모듈 설정을 구함
		 */ 
		function getModuleConfig($with_site = false) {
			// module의 model class
			$oModuleModel = &getModel('module');

			// allianceXE의 모듈 설정을 구함
			$config = $oModuleModel->getModuleConfig('alliance');

			// 설정된 스킨이 없을 경우 기본 스킨 지정
			$this->_setDefaultValue($config->skin->skin, 'default');

			// 기본값 지정
			if(!$config->sync) {
				$config->sync->contents_sync = 'Y';
				$config->sync->contents_sync_r = 'Y';
				$config->sync->contents_sync = 'Y';
				$config->sync->contents_sync_r = 'Y';
				$config->sync->member_sync = 'Y';
				$config->sync->member_sync_r = 'Y';
			}

			// 사이트 목록을 구함
			if($with_site) {
				$args->s_status = 'accepted';
				$args->sort_index = 'title';
				$sites = $this->getSites($args);
				$config->sites = $sites;
			}

			return $config;
		}

		/**
		 * @brief alliance_info 테이블에 저장된 연합 정보를 구함
		 */
		function getAllianceInfo() {
			$output = executeQuery('alliance.getAllianceInfo');
			if($output->toBool()) $output->data->count = $this->getSiteCount('accepted');
			return $output->data;
		}

		/**
		 * @brief 연합 관리자인지 확인
		 */
		function isGranted($alliance_info) {
			if(!$alliance_info) return true;
			if($this->current_url == $alliance_info->parent_domain) return true;
			return false;
		}

		/**
		 * @brief 유효한 도메인인지 확인
		 */
		function isValidDomain($domain, $check_agent = true) {
			// 도메인이 넘어오지 않았거나 현재 사이트의 도메인과 같으면 return false
			if(!$domain || $domain == $this->current_url) return false;

			// User-Agent와 Content-Type을 확인
			if($check_agent) {
				$user_agent = $_SERVER['HTTP_USER_AGENT'];
				$content_type = $_SERVER['CONTENT_TYPE'];

				// User-Agent에 XpressEngine allianceXE module임을 명시해놓지 않았다면 return false
				if(strpos($user_agent, 'XpressEngine allianceXE module') === false) return false;

				// Content-Type이 application/json이 아니라면 return false
				if($content_type != AXE_CONTENT_TYPE) return false;
			}

			return true;
		}

		/**
		 * @brief 소속 사이트 수를 구함
		 */
		function getSiteCount($status = null) {
			$args->s_status = $status;
			$output = executeQuery('alliance.getSiteCount', $args);
			return (int)$output->data->count;
		}

		function getMemberInfoByUserId($user_id, $site_srl) {
			$args->user_id = $user_id;
			$args->site_srl = $site_srl;
			$output = executeQuery('alliance.getMemberInfoByUserId', $args);
			return $output->data;
		}

		function getMemberSrlByUserId($user_id, $site_srl) {
			$args->user_id = $user_id;
			$args->site_srl = $site_srl;
			$output = executeQuery('alliance.getMemberSrlByUserId', $args);
			return $output->data->member_srl;
		}

		function getMemberAuth($member_srl, $site_srl) {
			$args->member_srl = $member_srl;
			$args->site_srl = $site_srl;
			$output = executeQuery('alliance.getMemberAuth', $args);
			return $output->data;
		}

		function getMemberAuthByUserId($user_id, $site_srl) {
			$args->user_id = $user_id;
			$args->site_srl = $site_srl;
			$output = executeQuery('alliance.getMemberAuthByUserId', $args);
			return $output->data;
		}

		function getMemberAuths($member_srl) {
			$args->member_srl = $member_srl;
			$output = executeQueryArray('alliance.getMemberAuths', $args);
			return $output->data;
		}

		function getMemberInfoByAuthkey($authkey) {
			if(!$authkey) return;

			$args->authkey = $authkey;
			$output = executeQuery('alliance.getMemberInfo', $args);
			if(!$output->toBool()) return $output;
			if(!$output->data) return;

			$oMemberModel = &getModel('member');
			$member_info = $oMemberModel->arrangeMemberInfo($output->data);

			return $member_info;
		}

		/**
		 * @brief AJAX API
		 */
		function getAllianceAPI() {
			// 결과 출력을 XMLRPC로 지정
			Context::setRequestMethod('JSON');

			// 요청받은 API type을 구함
			$getType = Context::get('gettype');
			switch($getType) {
				// 사이트 목록
				case 'site_list':
					$this->site_list();
					break;
				// 댓글 목록
				case 'comments':
					$this->comments();
					break;
				default:
					return new Object(-1, 'invalid_api');
			}
		}

		/**
		 * @brief 사이트 목록을 구함 (API)
		 */
		function site_list() {
			$document_srl = (int)Context::get('document_srl');
			$site_args->s_status = 'accepted';
			$site_list = $this->getSites($site_args);

			$this->add('document_srl', $document_srl);
			$this->add('site_list', $site_list);
		}

		/**
		 * @brief 댓글 목록을 구함 (API)
		 */
		function comments() {
			// 넘어온 도메인을 확인
			$domain = Context::get('domain');
			if(!$domain) return new Object(-1, 'msg_invalid_request');

			// 문서 번호가 넘어왔는지 확인
			$document_srl = Context::get('document_srl');
			if(!$document_srl) return new Object(-1, 'msg_invalid_request');

			// 넘어온 문서 번호로 콘텐츠 ID를 구함
			$content_args->document_srl = $document_srl;
			$output = executeQuery('alliance.getContentIdByDocumentSrl', $content_args);

			// 콘텐츠 ID를 찾을 수 없다면 동작 중단
			$content_id = $output->data->content_id;
			if(!$content_id) return new Object(0, 'msg_not_exists_contents');

			// allianceXE의 controller class
			$oAllianceController = &getController('alliance');

			$params['content_id'] = $content_id;

			// 댓글 목록 요청을 보냄
			$buff = $oAllianceController->sendRequest('getComments', $domain, $params);
			if(!$buff) return new Object(-1, 'msg_cannot_get_comments');

			// JSON class
			$oAllianceController->loadJSON($json);

			// JSON 디코딩
			$output = $json->decode($buff);

			// API 요청 성공 시
			if($output->message == 'success') {
				$comment_list = $output->comments;
			} else {
				return new Object(-1, $output->message ? $output->message : 'msg_cannot_get_comments');
			}

			$this->add('comment_list', $comment_list);
		}
	}
?>
