<?php

namespace ExampleVersion;

// TODO: <1.0.0 This has to be in the first major release
function doFoo():void {
    // TODO >123.4: Must fix this or bump the version
}

// TODO: <1.0.0
// TODO: <1.0
// TODO: 1.0

// should not error in TodoByVersionRule
// TODO php:8.0.0
// TODO php:8.0
// TODO php:8

// should not error because too unspecific
// TODO: 1 abc
