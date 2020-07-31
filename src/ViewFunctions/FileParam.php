<?php

namespace App\ViewFunctions;

class FileParam extends ViewFunction
{
    /** @var string The function name */
    protected $name = 'file_param';

    /**
     * Get the human readable file size from a file object.
     *
     * @param FileParam $file A file object
     *
     * @return string
     */
    public function __invoke(string $file): string
    {
        return substr(sprintf('%o', fileperms($file)), -4) . (fileowner($file)? '  ' . fileowner($file):'');
    }
}
