<?php

/** TODO APP-123 please change me */
#[SomeAttribute]
class Foo {}

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class SomeAttribute {}
