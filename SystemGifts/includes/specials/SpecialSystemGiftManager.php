<?php
/**
 * Special:SystemGiftManager -- a special page to create new system gifts
 * (awards)
 *
 * @file
 * @ingroup Extensions
 */

class SystemGiftManager extends SpecialPage {

	public function __construct() {
		parent::__construct( 'SystemGiftManager'/*class*/, 'awardsmanage'/*restriction*/ );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// make sure user has the correct permissions
		$this->checkPermissions();

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Set the page title, robot policies, etc.
		$out->setArticleRelated( false );
		$out->setRobotPolicy( 'noindex,nofollow' );

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.special.systemgiftmanager.css' );

		if ( $request->wasPosted() ) {
			$g = new SystemGifts();

			if ( !$request->getInt( 'id' ) ) {
				// Add the new system gift to the database
				$gift_id = $g->addGift(
					$request->getVal( 'gift_name' ),
					$request->getVal( 'gift_description' ),
					$request->getVal( 'gift_category' ),
					$request->getInt( 'gift_threshold' )
				);
				$out->addHTML(
					'<span class="view-status">' .
					$this->msg( 'ga-created' )->plain() .
					'</span><br /><br />'
				);
			} else {
				$gift_id = $request->getInt( 'id' );
				$g->updateGift(
					$gift_id,
					$request->getVal( 'gift_name' ),
					$request->getVal( 'gift_description' ),
					$request->getVal( 'gift_category' ),
					$request->getInt( 'gift_threshold' )
				);
				$out->addHTML(
					'<span class="view-status">' .
					$this->msg( 'ga-saved' )->plain() .
					'</span><br /><br />'
				);
			}
			$g->updateSystemGifts();
			$out->addHTML( $this->displayForm( $gift_id ) );
		} else {
			$gift_id = $request->getInt( 'id' );
			if ( $gift_id || $request->getVal( 'method' ) == 'edit' ) {
				$out->addHTML( $this->displayForm( $gift_id ) );
			} else {
				$out->addHTML(
					'<div><b><a href="' .
					htmlspecialchars( $this->getPageTitle()->getFullURL( 'method=edit' ) ) . '">' .
						$this->msg( 'ga-addnew' )->plain() . '</a></b></div>'
				);
				$out->addHTML( $this->displayGiftList() );
			}
		}
	}

	/**
	 * Display the text list of all existing system gifts and a delete link to
	 * users who are allowed to delete gifts.
	 *
	 * @return string HTML
	 */
	function displayGiftList() {
		$output = ''; // Prevent E_NOTICE
		$page = 0;
		$per_page = 50;
		$listLookup = new SystemGiftListLookup( $per_page, $page );
		$gifts = $listLookup->getGiftList();
		$user = $this->getUser();

		if ( $gifts ) {
			foreach ( $gifts as $gift ) {
				$deleteLink = '';
				if ( $user->isAllowed( 'awardsmanage' ) ) {
					$removePage = SpecialPage::getTitleFor( 'RemoveMasterSystemGift' );
					$deleteLink = '<a class="ga-remove-link" href="' .
						htmlspecialchars( $removePage->getFullURL( "gift_id={$gift['id']}" ) ) .
						'">' . $this->msg( 'delete' )->plain() . '</a>';
				}

				$output .= '<div class="Item">
					<a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL( 'id=' . $gift['id'] ) ) . '">' .
						$gift['gift_name'] . '</a> ' .
						$deleteLink . '</div>' . "\n";
			}
		}

		return '<div id="views">' . $output . '</div>';
	}

	function displayForm( $gift_id ) {
		$form = '<div><b><a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL() ) .
			'">' . $this->msg( 'ga-viewlist' )->plain() . '</a></b></div>';

		if ( $gift_id ) {
			$gift = SystemGifts::getGift( $gift_id );
		}

		$form .= '<form action="" method="post" enctype="multipart/form-data" name="gift">
		<table>
			<tr>
				<td class="view-form">' . $this->msg( 'ga-giftname' )->plain() . '</td>
				<td class="view-container"><input type="text" size="45" class="createbox" name="gift_name" value="' . ( $gift['gift_name'] ?? '' ) . '"/></td>
			</tr>
			<tr>
				<td class="view-form" valign="top">' . $this->msg( 'ga-giftdesc' )->plain() . '</td>
				<td class="view-container"><textarea class="createbox" name="gift_description" rows="2" cols="30">' . ( $gift['gift_description'] ?? '' ) . '</textarea></td>
			</tr>
			<tr>
				<td class="view-form">' . $this->msg( 'ga-gifttype' )->plain() . '</td>
				<td class="view-container">
					<select name="gift_category">' . "\n";
			$g = new SystemGifts();
			foreach ( $g->getCategories() as $category => $id ) {
				$sel = '';
				if ( isset( $gift['gift_category'] ) && $gift['gift_category'] == $id ) {
					$sel = ' selected="selected"';
				}
				$indent = "\t\t\t\t\t\t";
				$form .= $indent . '<option' . $sel .
					" value=\"{$id}\">{$category}</option>\n";
			}
			$form .= "\t\t\t\t\t" . '</select>
				</td>
			</tr>
		<tr>
			<td class="view-form">' . $this->msg( 'ga-threshold' )->plain() . '</td>
			<td class="view-container"><input type="text" size="25" class="createbox" name="gift_threshold" value="' .
				( $gift['gift_threshold'] ?? '' ) . '"/></td>
		</tr>';

		if ( $gift_id ) {
			$sgml = SpecialPage::getTitleFor( 'SystemGiftManagerLogo' );
			$systemGiftIcon = new SystemGiftIcon( $gift_id, 'l' );
			$icon = $systemGiftIcon->getIconHTML();

			$form .= '<tr>
			<td class="view-form" valign="top">' . $this->msg( 'ga-giftimage' )->plain() . '</td>
			<td class="view-container">' .
				$icon .
				'<a href="' . htmlspecialchars( $sgml->getFullURL( 'gift_id=' . $gift_id ) ) . '">' .
					$this->msg( 'ga-img' )->plain() . '</a>
				</td>
			</tr>';
		}

		if ( isset( $gift['gift_id'] ) ) {
			$button = $this->msg( 'edit' )->plain();
		} else {
			$button = $this->msg( 'ga-create-gift' )->plain();
		}

		$form .= '<tr>
		<td colspan="2">
			<input type="hidden" name="id" value="' . ( $gift['gift_id'] ?? '' ) . '" />
			<input type="button" class="createbox" value="' . $button . '" size="20" onclick="document.gift.submit()" />
			<input type="button" class="createbox" value="' . $this->msg( 'cancel' )->plain() . '" size="20" onclick="history.go(-1)" />
		</td>
		</tr>
		</table>

		</form>';
		return $form;
	}
}
