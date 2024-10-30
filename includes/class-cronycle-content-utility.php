<?php

/**
 * All the utility functions of the plugin.
 *
 * @link       http://cronycle.com
 * @since      1.0.0
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/includes
 */

/**
 * All the utility functions of the plugin.
 *
 * Defines the static utility functions required in the plugin
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/includes
 * @author     Cronycle
 */
class CronycleContentUtility
{
    /**
     * Converts hashtags, user mentions and urls in tweet text to anchor tags.
     *
     * @since    1.0.0
     * @access   public
     * @var     string      $tweet_text     Tweet text to linkify.
     * @return  string      Linkified text.
     */
    public static function tweet_text_to_html($tweet_text)
    {
        // urls
        $regex = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
        $anchor = "<a href=\"$0\" target=\"_blank\">$0</a>";
        $tweet_text = preg_replace($regex, $anchor, $tweet_text);
        
        // hashtags
        $regex = "/#([a-z_0-9]+)/i";
        $anchor = "<a href=\"http://twitter.com/search/$1\" target=\"_blank\">$0</a>";
        $tweet_text = preg_replace($regex, $anchor, $tweet_text);
        
        // user mentions
        $regex = "/@([a-z_0-9]+)/i";
        $anchor = "<a href=\"http://twitter.com/$1\" target=\"_blank\">$0</a>";
        $tweet_text = preg_replace($regex, $anchor, $tweet_text);

        return $tweet_text;
    }

    public static function var_dump_str($var)
    {
        ob_start();
        var_dump($var);
        return ob_get_clean();
    }

    /**
     * Checks whether a URL is valid or not.
     *
     * @since    4.0.2
     * @access   public
     * @var     string      $url     URL to check.
     * @return  string      true if valid else false.
     */
    public static function is_valid_url($url)
    {
        return esc_url_raw($url) === $url;
    }

    public static function nlToBr($str)
    {
        return str_replace(array("\r\n", "\r", "\n", "\\r", "\\n", "\\r\\n"), "<br/>", $str);
    }
}
