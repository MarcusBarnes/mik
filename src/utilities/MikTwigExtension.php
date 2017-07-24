<?php

namespace mik\utilities;

/**
 * Class to provide MIK-specific Twig extensions.
 *
 * See the InsertXmlFromTemplate metadata manipulator and the Templated
 * metadataparser for examples of how to implemnt methods defined in this class.
 */
class MikTwigExtension
{
    /**
     * Custom Twig filter to truncate a string to a specific length.
     *
     * Will truncate at the prceding word boundary, which means that
     * the returned string may be empty.
     *
     * Example: <title>{{ Title|TwigTruncate(20) }} [...]</title>
     *
     * @param string $string
     *   The string to truncate.
     * @param int $length
     *   The length at which to truncate the string.
     *
     * @return string
     *   The truncated string. If the wordsafe string has been truncated
     *   to be '', the original string is returned instead.
     */
    public static function twigTruncate($string, $length)
    {
        // First truncate the string.
        $truncated = substr($string, 0, $length);
        // Then determine what the nearest preceding word boundary is,
        // tokenizing the words on ' '.
        $tokenized_words = explode(' ', $truncated);
        array_pop($tokenized_words);
        $wordsafe_string = implode(' ', $tokenized_words);

        if (strlen($wordsafe_string) === 0) {
            return $string;
        } else {
            return $wordsafe_string;
        }
    }
}
