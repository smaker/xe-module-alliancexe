/**
 * @brief 연합 메시지 발송
 */
function completeInsertMessage(ret_obj) {
	var message = ret_obj['message'];
	var url = current_url.setQuery('act', 'dispAllianceAdminManageMessages');

	alert(message);
	location.href = url;
}

/**
 * @brief 연합 메시지 삭제
 */
function completeDeleteMessage(ret_obj) {
	var message = ret_obj['message'];
	var url = current_url.setQuery('act', 'dispAllianceAdminManageMessages');

	alert(message);
	location.href = url;
}

/**
 * @brief 사이트 정보 수정
 */
function completeModifySite(ret_obj) {
	var message = ret_obj['message'];
	var redirect_act = ret_obj['redirect_act'];

	alert(message);

	if(redirect_act) {
		var url = current_url.setQuery('act', redirect_act, 'redirect_url', '');
		location.href = url;
	}
}

/**
 * @brief 연합 만들기
 */
function completeCreate(ret_obj) {
	var message = ret_obj['message'];

	alert(message);

	location.href = current_url.setQuery('act', 'dispAllianceAdminInfo');
}

/**
 * @brief 연합 해체
 */
function completeBreakup(ret_obj) {
	var message = ret_obj['message'];

	alert(message);

	location.href = current_url.setQuery('act', 'dispAllianceAdminContent');
}

function completeDeleteContent(ret_obj) {
	var message = ret_obj['message'];
	alert(message);
	location.href = current_url.setQuery('act', 'dispAllianceAdminManageContents');
}

function completeCancleContentSync(ret_obj) {
	var message = ret_obj['message'];
	alert(message);
	location.href = current_url.setQuery('act', 'dispAllianceAdminManageContents');
}
/**
 * @brief 사이트 추가, 연합 추방
 */
function completeSiteCommand(ret_obj) {
	var message = ret_obj['message'];
	alert(message);
	location.href = current_url.setQuery('act', 'dispAllianceAdminManageSites');
}

function completeMemberSync(ret_obj) {
	alert(ret_obj['message']);
	location.reload();
}