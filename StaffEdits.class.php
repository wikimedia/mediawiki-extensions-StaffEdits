<?php

class StaffEdits {
	/**
	 * Display the tag selector drop-down menu on action=edit view.
	 *
	 * @param EditPage $editPage
	 * @param OutputPage $out
	 * @return bool
	 */
	public static function onEditPage( EditPage $editPage, OutputPage $out ) {
		// If the user isn't allowed to tag their edits as staff edits, get the
		// hell out of here.
		if ( !$out->getUser()->isAllowed( 'staffedit' ) ) {
			return true;
		}

		// Ideally there'd be a better way to do this, but once again good ol'
		// MW tries to be a tad bit too smart and sacrifices customizability in
		// favor of "security" of some kind. And EditPage just outright sucks,
		// too. But I think we all can agree on the fact that our staff members
		// don't really need to give a damn about the copyright warning, as they
		// should know the basics of (c)-right already. So let's just inject
		// the selector below that -- at least it's still above div.editButtons!
		$staffEditMsg = $out->msg( 'staffedit' )->plain();
		$noneMsg = $out->msg( 'staffedit-none' )->plain();
		$editPage->editFormTextAfterWarn .= wfMessage( 'staffedit-selector' )->plain() .
		"<select name=\"staffedit-tag\">
			<option value=\"\">{$noneMsg}</option>
			<option value=\"staffedit\">{$staffEditMsg}</option>
		</select>";

		return true;
	}

	/**
	 * Add our new tag to the array of existing tags.
	 *
	 * @param array &$tags
	 * @return bool
	 */
	public static function onListDefinedTags( array &$tags ) {
		$tags[] = 'staffedit';
		return true;
	}

	/**
	 * RecentChange_save hook handler that tags staff edits as such when
	 * requested.
	 *
	 * @param RecentChange $rc
	 * @return bool
	 */
	public static function onRecentChange_save( RecentChange $rc ) {
		global $wgRequest;

		// Paranoia -- permission check, just in case
		if ( !$rc->getPerformer()->isAllowed( 'staffedit' ) ) {
			return true;
		}

		$staffEditTag = $wgRequest->getVal( 'staffedit-tag' );

		$logType = $rc->getAttribute( 'rc_log_type' );
		// Only apply the tag for edits, nothing else, and only if we were given
		// a tag to apply (!)
		if ( is_null( $logType ) && !empty( $staffEditTag ) ) {
			$rcId = $rc->getAttribute( 'rc_id' );
			$revId = $rc->getAttribute( 'rc_this_oldid' );
			$logId = $rc->getAttribute( 'rc_logid' );
			// If (when?) we want to support multiple _real_ options (i.e.
			// something else than none & staffedit) in the selector, we'll
			// need to change the 'staffedit' below to $staffEditTag to fetch
			// the tag name from the request data. Until that, this approach is
			// sufficient enough for our needs.
			ChangeTags::addTags( 'staffedit', $rcId, $revId, $logId );
		}

		return true;
	}
}