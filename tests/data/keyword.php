<?php

namespace Keyword;

function doFoo():void {

}

// TODO: keyword1 comment1
// TODO: keyword2 comment2
// TODO: kEywORd1 comment3
// TODO keyword1 comment4
//TODO: keyword1 comment5
//TODO keyword1 comment6
//TODO    keyword1 comment7

// just a comment
class X {}

//TODO: keyword1 commentX

// TODO: keyword1
function doFooBar():void {

}

/**
 * other text
 *
 * @todo keyword1 class comment
 * @TODO keyword1 - class comment
 *   more comment data
 */
class Z {
    // TODO: keyword1 method comment
    public function XY():void {
        // TODO: keyword1 in method comment1
        $x = 1;
        // TODO keyword1: in method comment2
    }
}

/**
 * @todo keyword1 - Convert to standard Drupal $content code.
 */

// @todo keyword1 Decide to fix all the broken instances of class as a string

// @todo: keyword1 fix it
// @todo keyword1: fix it
// todo - keyword1 fix it
// todo keyword1 - fix it

// TODO@lars keyword1 - fix it
// TODO@lars: keyword1 - fix it

/*
 * other text
 *
 * @todo keyword1 classic multi line comment
 *   more comment data
 */
