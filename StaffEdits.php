<?php
/**
 * StaffEdits -- allows to tag edits as "official staff edits" in the edit
 * view (action=edit)
 *
 * @file
 * @ingroup Extensions
 * @author Jack Phoenix
 * @link https://www.mediawiki.org/wiki/Extension:StaffEdits Documentation
 * @license https://en.wikipedia.org/wiki/Public_domain Public domain
 */
use MediaWiki\MediaWikiServices;

class StaffEdits {

	/**
	 * Returns an organization specific message key
	 *
	 * @param string $name
	 * @return string
	 */
	protected static function msgKey( $name ) {
		global $wgStaffEditsMessagePrefix;
		return $wgStaffEditsMessagePrefix . $name;
	}

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
		$staffEditMsg = $out->msg( self::msgKey( 'staffedit' ) )->escaped();
		$noneMsg = $out->msg( 'staffedit-none' )->escaped();
		$editPage->editFormTextAfterWarn .= $out->msg( 'staffedit-selector' )->escaped() .
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
		$tags[] = self::msgKey( 'staffedit' );
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
		if ( method_exists( $rc, 'getPerformerIdentity' ) ) {
			// MW 1.36+
			$user = MediaWikiServices::getInstance()->getUserFactory()
				->newFromUserIdentity( $rc->getPerformerIdentity() );
		} else {
			// MW 1.35
			$user = $rc->getPerformer();
		}
		if ( !$user->isAllowed( 'staffedit' ) ) {
			return true;
		}

		$addTag = ( $wgRequest->getVal( 'staffedit-tag' ) === 'staffedit' );

		$source = $rc->getAttribute( 'rc_source' );
		// Only apply the tag for edits, nothing else, and only if we were given
		// a tag to apply (!)
		if ( in_array( $source, [ RecentChange::SRC_EDIT, RecentChange::SRC_NEW ] ) && $addTag ) {
			$rcId = $rc->getAttribute( 'rc_id' );
			$revId = $rc->getAttribute( 'rc_this_oldid' );
			// In the future we might want to support different
			// types of staff edit tags
			ChangeTags::addTags( self::msgKey( 'staffedit' ), $rcId, $revId );
		}

		return true;
	}

	/**
	 * Registers, and marks as active, the staff edit change tag.
	 *
	 * @param array &$tags
	 * @return bool
	 */
	public static function onListDefinedAndActiveTags( array &$tags ) {
		$tags[] = self::msgKey( 'staffedit' );
		return true;
	}
}
