<?php

namespace ExampleTest;

function doFoo():void {

}

// TODO: 2023-12-14 Expired comment1
// TODO 2023-12-14 Expired comment2
//TODO: 2023-12-14 Expired comment3
//TODO 2023-12-14 Expired comment4
//TODO    2023-12-14 Expired comment5

// just a comment
class X {}

// TODO: 2199-01-15 will expire in the future
//TODO: 2023-12-14 Expired commentX

// TODO: 2023-12-14
function doFooBar():void {

}

/**
 * other text
 *
 * @todo 2023-12-14 class comment
 * @TODO 2023-12-13 - class comment
 *   more comment data
 */
class Z {
    // TODO: 2023-12-14 method comment
    public function XY():void {
        // TODO: 2023-12-14 in method comment1
        $x = 1;
        // TODO 2023-12-14: in method comment2
    }
}

/**
 * @todo 2023-12-14 - Convert to standard Drupal $content code.
 */

// @todo 2023-12-14 Decide to fix all the broken instances of class as a string

// @todo: 2023-12-14 fix it
// @todo 2023-12-14: fix it
// todo - 2023-12-14 fix it
// todo 2023-12-14 - fix it

// TODO@lars 2023-12-14 - fix it
// TODO@lars: 2023-12-14 - fix it

/*
 * other text
 *
 * @todo 2023-12-14 classic multi line comment
 *   more comment data
 */

// TODO: APP-123 fix it
// TODO@lars: APP-444 fix it
// todo@lars: APP-000 - fix it
