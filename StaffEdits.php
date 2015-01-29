<?php
/**
 * StaffEdits -- allows to tag edits as "official staff edits" in the edit
 * view (action=edit)
 *
 * @file
 * @ingroup Extensions
 * @version 0.1.1
 * @author Jack Phoenix <jack@countervandalism.net>
 * @link https://www.mediawiki.org/wiki/Extension:StaffEdits Documentation
 * @license https://en.wikipedia.org/wiki/Public_domain Public domain
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Go away.' );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'StaffEdits',
	'version' => '0.1.1',
	'author' => 'Jack Phoenix',
	'descriptionmsg' => 'staffedit-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:StaffEdits',
);

/**
 * A simple lowercase abbreviation of the
 * organization the Staff are a part of.
 *
 * So "sw" for ShoutWiki, or "wmf" for
 * the Wikimedia Foundation.
 *
 * Once you set this, you really shouldn't
 * change it.
 */
$wgStaffEditsMessagePrefix = 'sw';

$wgAvailableRights[] = 'staffedit';
$wgGroupPermissions['staff']['staffedit'] = true;

// Set up i18n
$wgMessagesDirs['StaffEdits'] = __DIR__ . '/i18n';

// Set up the extension itself
$wgAutoloadClasses['StaffEdits'] = __DIR__ . '/StaffEdits.class.php';

$wgHooks['EditPage::showEditForm:initial'][] = 'StaffEdits::onEditPage';
$wgHooks['ListDefinedTags'][] = 'StaffEdits::onListDefinedTags';
$wgHooks['RecentChange_save'][] = 'StaffEdits::onRecentChange_save';
$wgHooks['ListDefinedTags'][] = 'StaffEdits::onListDefinedAndActiveTags';
$wgHooks['ChangeTagsListActive'][] = 'StaffEdits::onListDefinedAndActiveTags';
