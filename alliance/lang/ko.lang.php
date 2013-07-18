<?php
	/**
	 * @file lang/ko.lang.php
	 * @author SMaker (dowon2308@paran.com)
	 * @brief allianceXE 한국어 언어팩
	 */

	/**
	 * allianceXE module info
	 */
	$lang->alliance = 'allianceXE';
	$lang->about_alliancexe = 'allianceXE는 XE로 만들어진 사이트를 하나의 연합으로 구성할 수 있는 모듈입니다.';
	$lang->about_alliancexe_sync = '연합에 소속된 다른 사이트와 연동하기 위해서는 인증이 필요합니다.<br />인증 시 얻을 수 있는 혜택은 아래와 같습니다.<br /><ul><li>동일한 게시물을 연합에 소속된 사이트에 동시에 등록할 수 있습니다.</li></ul>';

	/**
	 * admin navigation
	 */
	$lang->index_page = '초기 화면';
	$lang->cmd_view_alliance_info = '연합 정보';
	$lang->cmd_alliance_setup = '연합 설정';
	$lang->cmd_alliance_sync_setup = '동기화 설정';
	$lang->cmd_alliance_skin_setup = '스킨 설정';
	$lang->cmd_manage_alliance_messages = '연합 메시지 관리';
	$lang->cmd_manage_alliance_sites = '연합 사이트 관리';
	$lang->cmd_manage_contents = '콘텐츠 관리';
	$lang->cmd_manage_members = '회원 관리';
	$lang->cmd_manage_logs = '로그 관리';

	/**
	 * buttons
	 */
	$lang->cmd_send_alliance_message = '연합 메시지 보내기';
	$lang->cmd_modify_site_info = '사이트 정보 수정';
	$lang->cmd_find_alliance = '연합 찾기';
	$lang->cmd_make_alliance = '연합 만들기';
	$lang->cmd_invite_site = '사이트 초대';
	$lang->cmd_manage_alliance_sync = '계정 관리';
	$lang->cmd_alliancexe_batch_member_sync = '회원 정보 일괄 동기화';
	$lang->cmd_alliancexe_batch_content_sync = '콘텐츠 일괄 동기화';

	/**
	 * commands
	 */
	$lang->cmd_invite = '초대';
	$lang->cmd_accept = '수락';
	$lang->cmd_refuse = '거절';
	$lang->cmd_alliance_leave = '연합 탈퇴';
	$lang->cmd_alliance_breakup = '연합 해체';
	$lang->cmd_yes = '예';
	$lang->cmd_no = '아니오';
	$lang->cmd_kick = '추방';
	$lang->cmd_join = '가입';

	$lang->status = '상태';
	$lang->invitation = '초대';
	$lang->invitation_accept = '초대 수락하기';

	$lang->alliance_title = '연합 이름';
	$lang->site_title = '사이트 이름';

	$lang->about_alliance_title = '연합의 이름을 입력해 주세요. (4~20자 사이)';
	$lang->about_site_title = '사이트를 나타내는 고유한 이름을 입력해 주세요. (2~20자 사이)';

	$lang->contents = '콘텐츠';
	$lang->received_message = '받은 메시지';
	$lang->sent_message = '보낸 메시지';

	$lang->parent_site = '부모 사이트';
	$lang->child_site = '자녀 사이트';
	$lang->target_site = '대상 사이트';
	$lang->source_site = '출처';
	$lang->create_date = '개설일';
	$lang->join_date = '가입일';
	$lang->suggest_alliance = '연합 제안';
	$lang->is_received = '받음';
	$lang->is_sent = '보냄';
	$lang->do_send = '보내기';
	$lang->do_receive = '받기';

	$lang->succeed_saved = '저장되었습니다.';
	$lang->succeed_invite = "해당 사이트에 초대 메시지를 보냈습니다.\n\n해당 사이트가 수락하면 연합에 가입됩니다.";
	$lang->succeed_accept = "연합에 가입되었습니다.";
	$lang->succeed_alliance_message_sent = '연합 메시지가 성공적으로 발송되었습니다.';
	$lang->succeed_alliance_message_sent_ = "연합 메시지가 성공적으로 발송되었습니다.\r\n(%s개의 사이트에서 수신하지 못햇습니다.)";
	$lang->succeed_alliance_message_sent__ = '연합 메시지를 발송하였으나 모든 사이트(%s개)에서 수신하지 못했습니다.';
	$lang->succeed_authed = '인증을 완료하였습니다.';
	$lang->succeed_unauthed = '인증 해제를 완료하였습니다.';
	$lang->succeed_refused = '해당 사이트에 거부 메시지를 보냈습니다.';
	$lang->succeed_created = '연합이 만들어졌습니다.';
	$lang->succeed_a_leaved = '연합에서 탈퇴하였습니다.';
	$lang->succeed_breakup = '연합이 해체되었습니다.';
	$lang->succeed_member_sync = '회원 정보가 동기화 되었습니다. (%s개의 회원 정보 동기화에 실패하였습니다)';
	$lang->succeed_content_sync = '콘텐츠가 동기화 되었습니다. (%s개의 콘텐츠 동기화에 실패하였습니다)';

	$lang->about_alliance_domain = 'http://를 제외한 사이트 URL을 입력해 주세요.<br /><br />(반드시 XE가 설치된 주소를 입력해 주세요.)';
	$lang->about_accept = '정말 초대에 수락하시겠습니까?';
	$lang->about_refuse = '정말 초대에 거부하시겠습니까?';
	$lang->about_a_auth = '해당 사이트에 아이디가 있다면 해당 아이디로 인증할 수 있습니다.';
	$lang->about_a_unauth = '본인 확인 후 인증 해제할 수 있습니다.<br />해당 사이트의 아이디와 비밀번호를 입력하여 본인 인증을 하시기 바랍니다.';
	$lang->about_a_leave = '본인 확인 후 해당 사이트에서 탈퇴할 수 있습니다.<br />해당 사이트의 아이디와 비밀번호를 입력하여 본인 인증을 하시기 바랍니다.';
	$lang->about_alliancexe_batch_member_sync = 'allianceXE 설치 이전에 가입한 회원 정보를 100개씩 동기화합니다.<br />회원이 많을 경우 시간이 오래걸리니 접속자가 적은 시간대에 사용하시기 바랍니다.';
	$lang->about_alliancexe_batch_content_sync = 'allianceXE 설치 이전에 등록된 콘텐츠를 100개씩 동기화합니다.<br />콘텐츠가 많을 경우 시간이 오래걸리니 접속자가 적은 시간대에 사용하시기 바랍니다.';

	$lang->invitation_messages = '초대 메시지가 <a href="%s"><strong>%s건</strong></a> 있습니다.';

	$lang->alliance_invitation = '초대 메시지';
	$lang->recently_alliance_message = '최근 받은 연합 메시지';

	$lang->not_exists_invitation_message = '아직까지 받은 연합 초대 메시지가 없습니다.';
	$lang->not_exists_recently_alliance_message = '최근 연합으로부터 받은 메시지가 없습니다.';

	/**
	 * messages
	 */
	$lang->msg_no_alliance_sites = '아직 등록된 사이트가 없습니다.';
	$lang->msg_no_received_alliance_messages = '아직 받은 연합 메시지가 없습니다.';
	$lang->msg_no_sent_alliance_messages = '아직 보낸 연합 메시지가 없습니다.';
	$lang->msg_no_alliance_contents = '아직 동기화된 콘텐츠가 없습니다.';
	$lang->msg_suggest_alliance = '아직 소속된 연합이 없으시군요!<br /><br />함께 할 연합을 찾아보시거나 연합을 직접 만들어 보시는 것은 어떤가요?';
	$lang->msg_failed_receive_message = '메시지를 수신하는데 실패하였습니다.';
	$lang->msg_cannot_send_message = '등록된 사이트가 없어 메시지를 보낼 수 없습니다.';
	$lang->msg_failed_invite = "대상 사이트가 응답하지 않습니다.\n\n아래 사항을 확인 하시기 바랍니다.\n\n1. 대상 사이트에 접속 가능한가?\n2. 대상 사이트에 allianceXE 모듈이 설치되어 있는가?";
	$lang->msg_failed_accept = "대상 사이트가 응답하지 않습니다.\n\n아래 사항을 확인 하시기 바랍니다.\n\n1. 대상 사이트가 해당 사이트에 사이트 초대 요청을 한 적이 있는가?\n2. 대상 사이트에 접속 가능한가?\n3. 대상 사이트에 allianceXE 모듈이 설치되어 있는가?";
	$lang->msg_invalid_access = '비정상적인 접근입니다!';
	$lang->msg_not_authorized_access = '인증되지 않은 접근입니다.';
	$lang->msg_already_joined_alliance_domain = '이미 연합에 가입된 사이트입니다!';
	$lang->msg_cannot_invite_me = '이미 다른 연합에 소속된 사이트입니다!';
	$lang->msg_cannot_auth = "해당 사이트의 문제로 인증을 할 수 없습니다.\n\n나중에 다시 시도해 보세요.";
	$lang->msg_cannot_unauth = "해당 사이트의 문제로 인증 해제를 할 수 없습니다.\n\n나중에 다시 시도해 보세요.";
	$lang->msg_failed_auth = "인증에 실패하였습니다.\n\n아이디와 비밀번호를 올바르게 입력했는지 확인해 보세요.";
	$lang->msg_failed_unauth = "인증 해제에 실패하였습니다.\n\n아이디와 비밀번호를 올바르게 입력했는지 확인해 보세요.";
	$lang->msg_already_authed = '이미 인증이 되어 있습니다.';
	$lang->msg_already_unauthed = '인증이 되어 있지 않습니다.';
	$lang->msg_cannot_a_leave = "해당 사이트의 문제로 탈퇴할 수 없습니다.\n\n나중에 다시 시도해 보세요";
	$lang->msg_already_unauthed = '인증이 되어 있지 않습니다.';
	$lang->msg_wrong_alliance_info = sprintf('연합 정보에 문제가 있습니다.<br /><br /><a href="%s" class="button green strong"><span>문제 해결하기</span></a>', getUrl('', 'module', 'admin', 'act', 'dispAllianceAdminRepair'));
	$lang->msg_axe_api_error = '아래와 같은 이유로 실패하였습니다.\r\n--------------------\r\n\r\n';
	$lang->msg_not_exists_alliance_domain = '등록되지 않은 도메인입니다.';
	$lang->msg_cannot_invite_site = '먼저 연합을 만드셔야 초대하실 수 있습니다.';
	$lang->msg_cannot_get_comments = "해당 사이트에서 댓글 목록을 받아오지 못했습니다.\n\n나중에 다시 시도해 주세요.";
	$lang->msg_cannot_leave_adm = '최고 관리자 권한이 있어서 탈퇴할 수 없습니다.';

	/**
	 * 동기화 상태
	 */
	$lang->alliance_statues = array(
		'waiting' => '대기',
		'accepted' => '동기화',
		'denied' => '거부'
	);

	
	$lang->confirm_invite_site = '정말 입력하신 사이트를 초대하시겠습니까?';
	$lang->confirm_accept = '수락하시겠습니까?';
	$lang->confirm_batch_member_sync = '정말 회원 정보를 일괄적으로 동기화하시겠습니까?\n\n회원이 많을 경우 시간이 오래걸리니 접속자가 적은 시간대에 사용하시기 바랍니다.';

	$lang->__search_target_list__ = array(
	'source' => '출처',
	'domain' => '도메인',
	'content' => '내용',
	'less_date' => '날짜 (이하)',
	'more_date' => '날짜 (이상)'
	);

	/**
	 * 콘텐트 삭제
	 */
	$lang->cmd_delete_content_myself = '콘텐츠 발행 취소';
	$lang->cmd_delete_content = '콘텐츠 완전 삭제';
	$lang->about_delete_content = '정말 위의 콘텐츠의 발행을 취소하시겠습니까?<br /><br />콘텐츠는 유지되나 연합에 소속된 다른 사이트에서는 삭제됩니다.';

	/**
	 * 동기화 설정
	 */
	$lang->contents_sync = '콘텐츠 동기화';
	$lang->member_sync = '회원 정보 동기화';
	$lang->guest_sync = '비회원 콘텐츠 동기화';
	$lang->about_guest_sync = '비회원이 등록한 콘텐츠를 동기화 보내거나 받을 수 있습니다.';
	$lang->include_sync_target = '선택된 대상만 동기화';
	$lang->exclude_sync_target = '선택된 대상을 동기화에서 제외';

	/**
	 * 동기화 설정 > 콘텐츠 동기화
	 */
	$lang->contents_title_prefix = '콘텐츠 제목 머릿말';
	$lang->contents_title_prefix_opt = '[사이트 제목]';
	$lang->contents_title_prefix_opt2 = '사용자 지정 머릿말';
	$lang->about_contents_title_prefix = '콘텐츠를 받을 때 콘텐츠를 구분하기 위해 콘텐츠 제목 앞에 머릿말을 붙일 수 있습니다.';
	$lang->when_receive = '동기화 받을 때';
	$lang->when_send = '동기화 보낼 때';

	$lang->auth_date = '인증일';
	$lang->authed = '인증됨';
	$lang->not_authed = '인증되지 않음';
	$lang->cmd_auth = '인증하기';
	$lang->cmd_unauth = '인증해제';

	$lang->alliance_member_count = '소속된 사이트 수';

	/**
	 * 연합 해체
	 */
	$lang->warning_breakup = "정말 연합을 해체하시겠습니까?\n\n<strong>해체하시면 동기화된 콘텐츠와 모든 연합 정보가 사라집니다.</strong>\n\n한 번 해체하시면 돌이킬 수 없으니 신중히 생각하신 후에 결정을 내리시기 바랍니다.";
	$lang->confirm_breakup = '정말 연합을 해체하시겠습니까?\n\n해체하시면 동기화된 콘텐츠와 모든 연합 정보가 사라집니다.\n\n한 번 해체하시면 돌이킬 수 없으니 신중히 생각하신 후에 결정을 내리시기 바랍니다.';

	/**
	 * 연합 탈퇴
	 */
	$lang->warning_a_leave = "정말 연합에서 탈퇴하시겠습니까?\n\n<strong>해체하시면 동기화 받은 콘텐츠와 연합 정보가 사라집니다.</strong>\n\n한 번 해체하시면 돌이킬 수 없으니 신중히 생각하신 후에 결정을 내리시기 바랍니다.";
	$lang->confirm_a_leave = '정말 연합에서 탈퇴하시겠습니까?\n\n해체하시면 동기화된 동기화 받은 콘텐츠와 연합 정보가 사라집니다.\n\n한 번 해체하시면 돌이킬 수 없으니 신중히 생각하신 후에 결정을 내리시기 바랍니다.';

	/**
	 * 연합 추방
	 */
	$lang->warning_kick = "정말 위의 사이트를 연합에서 추방하시겠습니까?\n\n<strong>추방하시면 위의 사이트로부터 동기화된 콘텐츠가 사라집니다.</strong>\n\n한 번 추방하시면 돌이킬 수 없으니 신중히 생각하신 후에 결정을 내리시기 바랍니다.";
	$lang->confirm_kick = '정말 연합에서 추방하시겠습니까?\n\n추방하시면 해당 사이트로부터 동기화된 콘텐츠가 사라집니다.\n\n한 번 추방하시면 돌이킬 수 없으니 신중히 생각하신 후에 결정을 내리시기 바랍니다.';
	/**
	 * member module
	 **/
	$lang->invalid_user_id= '존재하지 않는 사용자 아이디입니다.';
	$lang->invalid_password = '잘못된 비밀번호입니다.';
	$lang->msg_user_denied = '입력하신 아이디의 사용이 중지되셨습니다.';
	$lang->msg_user_not_confirmed = '아직 메일 인증이 이루어지지 않았습니다. 메일을 확인해 주세요.';
	$lang->msg_user_limited = "입력하신 아이디는 %s 이후부터 사용하실 수 있습니다.";
?>