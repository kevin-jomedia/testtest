<?php

/*
 * This file is part of the Compiler package.
 *
 * (c) 2013 Kevin Simard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Compiler;

/**
 * Describes an exception that occured when target extension is invalid
 * 
 * @author Kevin Simard <ksimard@outlook.com>
 */
class InvalidTargetExtException extends Exception
{
    private $message = "Target extension is invalid, it must ends with one of the following extensions (css|js)";
}
