<?php
namespace Drupal\iish_conference;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\TranslationsApi;
use Drupal\iish_conference\API\Domain\SessionApi;
use Drupal\iish_conference\API\Domain\NetworkApi;
use Drupal\iish_conference\API\LoggedInUserDetails;

use Drupal\iish_conference\Markup\ConferenceHTML;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Miscellaneous conference functions that are often used
 */
class ConferenceMisc {

  /**
   * Return a human friendly text 'yes' or 'no' based on the given boolean value
   *
   * @param bool $booleanValue When 'true', 'yes' is returned, else 'no' is returned
   *
   * @return string Either 'yes' or 'no'
   */
  public static function getYesOrNo($booleanValue) {
    return $booleanValue ? iish_t('yes') : iish_t('no');
  }

  /**
   * Returns the human-friendly text for a gender
   *
   * @param string $genderKey The gender key
   *
   * @return string The human-friendly text
   */
  public static function getGender($genderKey) {
    $genders = self::getGenders();

    return $genders[$genderKey];
  }

  /**
   * Returns the human-friendly texts for genders
   *
   * @return array The human-friendly texts for genders
   */
  public static function getGenders() {
    return array(
      '' => '',
      'M' => iish_t('Male'),
      'F' => iish_t('Female'),
    );
  }

  /**
   * Returns the human-friendly text for a language coach/pupil volunteering
   *
   * @param string $langCoachPupilKey The language coach/pupil volunteering key
   *
   * @return string The human-friendly text for a language coach/pupil volunteering
   */
  public static function getLanguageCoachPupil($langCoachPupilKey) {
    $langCoachPupils = self::getLanguageCoachPupils();

    return $langCoachPupils[$langCoachPupilKey];
  }

  /**
   * Returns the human-friendly texts for language coach/pupil volunteering
   *
   * @return array The human-friendly texts for language coach/pupil volunteering
   */
  public static function getLanguageCoachPupils() {
    return array(
      '' => iish_t('not applicable'),
      'coach' => iish_t('I would like to be an English Language Coach'),
      'pupil' => iish_t('I need some help from an English Language Coach'),
    );
  }

  /**
   * Returns the filesize in a human friendly format
   *
   * @param int|null $filesize The filesize in bytes
   *
   * @return string The filesize in bytes, KB or MB
   */
  public static function getReadableFileSize($filesize) {
    if (is_null($filesize) || ($filesize == 0)) {
      return '0 bytes';
    }

    if ($filesize / 1024 > 1) {
      if ($filesize / 1048576 > 1) {
        return round($filesize / 1048576, 2) . ' MB';
      }

      return round($filesize / 1024, 2) . ' KB';
    }

    return $filesize . ' bytes';
  }

  /**
   * A wrapper around preg_match that simply returns whether the string matches the pattern or not
   *
   * @param string $value The string to match
   * @param string $pattern The pattern the value should have
   *
   * @return bool Whether the value matches the pattern or not
   */
  public static function regexpValue($value, $pattern = '/^[0-9]+$/') {
    $ret = TRUE;

    if ($value !== '') {
      if ($pattern !== '') {
        if (preg_match($pattern, $value) === 0) {
          $ret = FALSE;
        }
      }
    }

    return $ret;
  }

  /**
   * Returns the amount in a human friendly readable format
   *
   * @param int|float $amount The amount
   * @param bool $amountInCents Whether the amount is given in cents, yes or no
   *
   * @return string The human friendly format
   */
  public static function getReadableAmount($amount, $amountInCents = FALSE) {
    if ($amountInCents) {
      $amount = $amount / 100;
    }

    return number_format($amount, 2) . ' EUR';
  }

  /**
   * Returns the block that informs the user how and who to contact for information
   *
   * @return string The HTML generating an info block
   */
  public static function getInfoBlock() {
    $email = SettingsApi::getSetting(SettingsApi::DEFAULT_ORGANISATION_EMAIL);
    return
      '<div class="eca_warning topmargin">'
      . iish_t('For any remarks or questions, please contact: ')
      . self::encryptEmailAddress($email)
      . '</div>';
  }

  /**
   * Protects again email harvesting by cutting up the email address into pieces
   *
   * @param string $email The email address in question
   * @param string $label The label of the email address
   *
   * @return MarkupInterface The HTML/Javascript to place in the document
   */
  public static function encryptEmailAddress($email, $label = '') {
    $email = trim($email);
    $label = ($label == '') ? $email : trim($label);
    return Link::fromTextAndUrl($label, Url::fromUri('mailto:' . $email))->toString();
    // TODO: return new Email($email, $label);
  }

  /**
   * Create a single line enumeration
   *
   * @param array $items The items to enumerate
   * @param string $seperator The default seperator to use
   * @param string $seperatorEnd The last seperator to use, 'and' by default
   *
   * @return string A single line enumeration
   */
  public static function getEnumSingleLine(array $items, $seperator = ', ', $seperatorEnd = NULL) {
    if ($seperatorEnd === NULL) {
      $seperatorEnd = ' ' . iish_t('and') . ' ';
    }

    $line = '';
    $items = array_values($items);
    foreach ($items as $i => $item) {
      if ($i > 0) {
        $line .= ($i < (count($items) - 1)) ? $seperator : $seperatorEnd;
      }
      $line .= $item;
    }

    return $line;
  }

  /**
   * Only returns the first part of a long piece of text, with (...) indicating that the original is longer.
   * Users can toggle between showing all text or the short version.
   * If nothing was cut, the (...) will not appear.
   *
   * @param string $text The text to cut (No HTML!)
   * @param int $numberOfWords The number of allowed words from the start
   *
   * @return ConferenceHTML The HTML that can be used
   */
  public static function getHTMLForLongText($text, $numberOfWords = 50) {
    $newText = implode(' ', array_slice(explode(' ', $text), 0, $numberOfWords));

    if (strlen($newText) < strlen($text)) {
      return new ConferenceHTML(
        '<div class="less-text">' . $newText
        . ' ... <a href="" class="more">(Show more)</a></div>'
        . '<div class="more-text">' . $text . ' '
        . '<a href="" class="less">(Show less)</a></div>', TRUE);
    }
    else {
      return new ConferenceHTML($newText, TRUE);
    }
  }

  /**
   * Tests if a given 'last date' is still open (last day has not yet passed)
   *
   * @param int $lastDate The 'last date' as a UNIX timestamp
   * @param null|int $today Another UNIX timestamp if you want to change 'today'
   *
   * @return bool Whether according to the last date, it is still open
   */
  public static function isOpenForLastDate($lastDate, $today = NULL) {
    $today = ($today === NULL) ? strtotime('today') : $today;

    return $today <= $lastDate;
  }

  /**
   * Tests if a given 'start date' is still open (start day has passed)
   *
   * @param int $startDate The 'start date' as a UNIX timestamp
   * @param null|int $today Another UNIX timestamp if you want to change 'today'
   *
   * @return bool Whether according to the start date, it is still open
   */
  public static function isOpenForStartDate($startDate, $today = NULL) {
    $today = ($today === NULL) ? strtotime('today') : $today;

    return $today >= $startDate;
  }

  /**
   * Whether the (logged in?) user may see the current online programme
   *
   * @return bool Whether the (logged in?) user may see the current online programme
   */
  public static function mayLoggedInUserSeeProgramme() {
    return (
      (SettingsApi::getSetting(SettingsApi::SHOW_PROGRAMME_ONLINE) == 1) ||
      LoggedInUserDetails::isCrew() ||
      LoggedInUserDetails::isChair() ||
      LoggedInUserDetails::isOrganiser() ||
      LoggedInUserDetails::isNetworkChair() ||
      LoggedInUserDetails::hasFullRights()
    );
  }

  /**
   * Override of the default t function of Drupal
   * Will translate the text first using the translations CMS API
   * It will then replace all occurrences of network and session in the text
   *
   * @param string $string A string containing the English string to translate
   * @param array $args An associative array of replacements to make after translation.
   *                                      Based on the first character of the key, the value is escaped and/or themed.
   *                                      See format_string() for details
   * @param bool $callOriginalTFunction Whether to include a call to the original t function
   *
   * @return TranslatableMarkup
   */
  public static function translate($string, array $args = array(), $callOriginalTFunction = TRUE) {
    $string = TranslationsApi::getTranslation($string);

    $string = self::replaceNetwork($string);
    $string = self::replaceSession($string);

    if ($callOriginalTFunction) {
      return t($string, $args);
    }
    else {
      return new TranslatableMarkup($string);
    }
  }

  /**
   * Replaces all occurences of 'network' in a string with the replacement found in the setting
   *
   * @param string $string The original text string
   *
   * @return string The new text string
   */
  public static function replaceNetwork($string) {
    $string = str_replace('network', NetworkApi::getNetworkName(TRUE, TRUE), $string);
    $string = str_replace('networks', NetworkApi::getNetworkName(FALSE, TRUE), $string);
    $string = str_replace('Network', NetworkApi::getNetworkName(TRUE, FALSE), $string);
    $string = str_replace('Networks', NetworkApi::getNetworkName(FALSE, FALSE), $string);

    return $string;
  }

  /**
   * Replaces all occurences of 'session' in a string with the replacement found in the setting
   *
   * @param string $string The original text string
   *
   * @return string The new text string
   */
  public static function replaceSession($string) {
    $string = str_replace('session', SessionApi::getSessionName(TRUE, TRUE), $string);
    $string = str_replace('sessions', SessionApi::getSessionName(FALSE, TRUE), $string);
    $string = str_replace('Session', SessionApi::getSessionName(TRUE, FALSE), $string);
    $string = str_replace('Sessions', SessionApi::getSessionName(FALSE, FALSE), $string);

    return $string;
  }
}