<?php

use PEAR_Sniffs_Commenting_ClassCommentSniff as BaseSniff;

/**
 * Chroma Class Comment Sniff
 *
 * Makes sure a class comment exists and has a minimum set of parameters
 * (author, package) and enforces a simple order on every file
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Chromabits\Standards\Chroma\Sniffs\Commenting
 */
class Chroma_Sniffs_Commenting_ClassCommentSniff extends BaseSniff
{
    /**
     * Custom tag definitions for the Chroma standard
     *
     * @var array
     */
    protected $tags = [
        '@category' => [
            'required' => false,
            'allow_multiple' => false,
        ],
        '@author' => [
            'required' => true,
            'allow_multiple' => true,
        ],
        '@copyright' => [
            'required' => false,
            'allow_multiple' => true,
        ],
        '@license' => [
            'required' => false,
            'allow_multiple' => false,
        ],
        '@version' => [
            'required' => false,
            'allow_multiple' => false,
        ],
        '@link' => [
            'required' => false,
            'allow_multiple' => true,
        ],
        '@see' => [
            'required' => false,
            'allow_multiple' => true,
        ],
        '@since' => [
            'required' => false,
            'allow_multiple' => false,
        ],
        '@deprecated' => [
            'required' => false,
            'allow_multiple' => false,
        ],
        '@package' => [
            'required' => true,
            'allow_multiple' => false,
        ],
        '@subpackage' => [
            'required' => false,
            'allow_multiple' => false,
        ],
    ];

    /**
     * Extract the first namespace found in the file.
     *
     * @param PHP_CodeSniffer_File $file
     *
     * @return string
     */
    protected function extractNamespace(
        PHP_CodeSniffer_File $file
    ) {
        $namespace = '';

        $tokens = $file->getTokens();

        $prev = $file->findNext(T_NAMESPACE, 0);

        for ($i = $prev + 2; $i < count($tokens); $i++) {
            if (!in_array($tokens[$i]['code'], [T_STRING, T_NS_SEPARATOR])) {
                break;
            }

            $namespace .= $tokens[$i]['content'];
        }

        return $namespace;
    }

    /**
     * Process the package tag.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param array $tags The tokens for these tags.
     *
     * @return void
     */
    protected function processPackage(
        PHP_CodeSniffer_File $phpcsFile,
        array $tags
    ) {
        $tokens = $phpcsFile->getTokens();
        foreach ($tags as $tag) {
            if ($tokens[($tag + 2)]['code'] !== T_DOC_COMMENT_STRING) {
                // No content.
                continue;
            }

            $namespace = $this->extractNamespace($phpcsFile);
            $content = $tokens[($tag + 2)]['content'];

            if ($namespace !== $content) {
                $phpcsFile->addError(
                    'Package name "%s" should be "%s"',
                    $tag,
                    'InvalidPackage',
                    [
                        $content,
                        $namespace
                    ]
                );
                continue;
            }

            if (PHP_CodeSniffer::isUnderscoreName($content) === true) {
                continue;
            }

            $newContent = str_replace(' ', '_', $content);
            $newContent = trim($newContent, '_');
            $newContent = preg_replace('/[^A-Za-z_]/', '', $newContent);
            $nameBits = explode('_', $newContent);
            $firstBit = array_shift($nameBits);
            $newName = strtoupper($firstBit{0}) . substr($firstBit, 1) . '_';
            foreach ($nameBits as $bit) {
                if ($bit !== '') {
                    $newName .= strtoupper($bit{0}) . substr($bit, 1) . '_';
                }
            }

            $error = 'Package name "%s" is not valid; consider "%s" instead';
            $validName = trim($newName, '_');
            $data = [
                $content,
                $validName,
            ];
            $phpcsFile->addError($error, $tag, 'InvalidPackage', $data);
        }
    }
}
