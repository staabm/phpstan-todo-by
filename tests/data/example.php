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
