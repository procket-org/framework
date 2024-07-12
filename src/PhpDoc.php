<?php

namespace Procket\Framework;

/**
 * Simple php document parser
 */
class PhpDoc
{
    /**
     * Parsed document cache
     * @var array[]
     */
    private static array $parsedDocsCache = [];

    /**
     * Parse document information
     *
     * ```
     * Returned array format:
     * [
     *      'summary' => 'Brief description',
     *      'description' => 'Detailed Description',
     *      'tags' => [...]
     * ]
     * ```
     *
     * @param string $comment
     * @return array
     */
    public static function parseDocInfo(string $comment): array
    {
        $cacheKey = md5(serialize(func_get_args()));
        if (isset(static::$parsedDocsCache[$cacheKey])) {
            return static::$parsedDocsCache[$cacheKey];
        }

        [, $summary, $description, $tags] = static::splitDocBlock(static::stripDocComment($comment));
        $tags = static::splitTagBlockIntoTagLines($tags);

        return static::$parsedDocsCache[$cacheKey] = [
            'summary' => $summary,
            'description' => $description,
            'tags' => $tags
        ];
    }

    /**
     * Strip document comment
     *
     * @param string $comment
     * @return string
     */
    private static function stripDocComment(string $comment): string
    {
        $comment = preg_replace('#[ \t]*(?:\/\*\*|\*\/|\*)?[ \t]?(.*)?#u', '$1', $comment);
        $comment = trim($comment);
        if (str_ends_with($comment, '*/')) {
            $comment = trim(substr($comment, 0, -2));
        }

        return str_replace(["\r\n", "\r"], "\n", $comment);
    }

    /**
     * Split document block
     *
     * @param string $comment
     * @return string[]
     */
    private static function splitDocBlock(string $comment): array
    {
        if (str_starts_with($comment, '@')) {
            return ['', '', '', $comment];
        }

        $comment = preg_replace('/\h*$/Sum', '', $comment);
        preg_match(
            '/
            \A
            # 1. Extract the template marker
            (?:(\#\@\+|\#\@\-)\n?)?
            # 2. Extract the summary
            (?:
              (?! @\pL ) # The summary may not start with an @
              (
                [^\n.]+
                (?:
                  (?! \. \n | \n{2} )     # End summary upon a dot followed by newline or two newlines
                  [\n.]* (?! [ \t]* @\pL ) # End summary when an @ is found as first character on a new line
                  [^\n.]+                 # Include anything else
                )*
                \.?
              )?
            )
            # 3. Extract the description
            (?:
              \s*        # Some form of whitespace _must_ precede a description because a summary must be there
              (?! @\pL ) # The description may not start with an @
              (
                [^\n]+
                (?: \n+
                  (?! [ \t]* @\pL ) # End description when an @ is found as first character on a new line
                  [^\n]+            # Include anything else
                )*
              )
            )?
            # 4. Extract the tags (anything that follows)
            (\s+ [\s\S]*)? # everything that follows
            /ux',
            $comment,
            $matches
        );
        array_shift($matches);

        while (count($matches) < 4) {
            $matches[] = '';
        }

        return $matches;
    }

    /**
     * Split tag block into tag lines
     *
     * @param string $tags
     * @return array
     */
    private static function splitTagBlockIntoTagLines(string $tags): array
    {
        $tags = trim($tags);

        $result = [];
        foreach (explode("\n", $tags) as $tagLine) {
            if ($tagLine !== '' && str_starts_with($tagLine, '@')) {
                $result[] = $tagLine;
            } else if (isset($result[count($result) - 1])) {
                $result[count($result) - 1] .= "\n" . $tagLine;
            }
        }

        return $result;
    }
}
