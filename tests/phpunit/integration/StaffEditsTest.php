<?php

namespace MediaWiki\Extension\StaffEdits\Tests\Integration;

use MediaWiki\Context\RequestContext;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWikiIntegrationTestCase;

/**
 * @covers StaffEdits
 * @group Database
 */
class StaffEditsTest extends MediaWikiIntegrationTestCase {
	/** @dataProvider provideAddsTagWhenAllowed */
	public function testAddsTagWhenAllowed( bool $userHasRight, bool $requestHasFlag, bool $shouldAddTag ): void {
		$this->setTemporaryHook(
			'ListDefinedTags',
			static function ( &$tags ) {
				$tags[] = 'staff-tag';
			}
		);
		$this->setGroupPermissions( 'sysop', 'staff-tag', true );
		$this->overrideConfigValue( 'StaffEditsTags', [ 'staff-tag' ] );
		$this->overrideConfigValue( 'StaffEditsMessagePrefix', '' );

		// Editing a page will eventually call StaffEdits::onRecentChange_save
		if ( $requestHasFlag ) {
			RequestContext::getMain()->getRequest()->setVal( 'staffedit-tag', 'staff-tag' );
		}
		$editStatus = $this->editPage(
			$this->getNonexistingTestPage(),
			'Test',
			'',
			NS_MAIN,
			$userHasRight ? $this->getTestSysop()->getAuthority() : $this->getTestUser()->getAuthority()
		);
		$this->assertStatusGood( $editStatus );
		DeferredUpdates::doUpdates();

		$changeTagsOnRevision = $this->getServiceContainer()->getChangeTagsStore()->getTags(
			$this->getDb(),
			null,
			$editStatus->getNewRevision()->getId()
		);
		if ( $shouldAddTag ) {
			$this->assertContains( 'staff-tag', $changeTagsOnRevision );
		} else {
			$this->assertNotContains( 'staff-tag', $changeTagsOnRevision );
		}

		$recentChange = $this->getServiceContainer()->getRecentChangeLookup()->getRecentChangeByConds( [
			'rc_this_oldid' => $editStatus->getNewRevision()->getId()
		] );
		$this->assertNotNull( $recentChange );

		$changeTagsOnRecentChange = $this->getServiceContainer()->getChangeTagsStore()->getTags(
			$this->getDb(),
			$recentChange->getAttribute( 'rc_id' )
		);
		if ( $shouldAddTag ) {
			$this->assertContains( 'staff-tag', $changeTagsOnRecentChange );
		} else {
			$this->assertNotContains( 'staff-tag', $changeTagsOnRecentChange );
		}
	}

	public static function provideAddsTagWhenAllowed(): array {
		return [
			'User does not have right' => [
				'userHasRight' => false,
				'requestHasFlag' => true,
				'shouldAddTag' => false,
			],
			'User has right but no request flag specified' => [
				'userHasRight' => true,
				'requestHasFlag' => false,
				'shouldAddTag' => false,
			],
			'User has right and request flag specified' => [
				'userHasRight' => true,
				'requestHasFlag' => true,
				'shouldAddTag' => true,
			],
		];
	}
}
