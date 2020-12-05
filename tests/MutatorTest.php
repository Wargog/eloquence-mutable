<?php

namespace Sofa\Eloquence\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Sofa\Eloquence\Mutator\Mutator;

class MutatorTest extends TestCase
{
    private $mutator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mutator = new MutatorStub;
    }

    /** @test */
    public function it_accepts_internal_function()
    {
        $callable = 'strtoupper';

        $this->assertEquals('FOO', $this->mutator->mutate('foo', $callable));
    }

    /** @test */
    public function it_accepts_mutator_class_method()
    {
        $callable = 'clip';

        $this->assertEquals('quick', $this->mutator->mutate('quick red fox', $callable));
    }

    /** @test */
    public function it_accepts_other_class_static_method()
    {
        $callable = 'Sofa\Eloquence\Tests\MutatorDummyInstantiable@multiply';

        $this->assertEquals(4, $this->mutator->mutate(2, $callable));
    }

    /** @test */
    public function it_accepts_other_class_instance_method()
    {
        $callable = 'Sofa\Eloquence\Tests\MutatorDummyInstantiable@divide';

        $this->assertEquals(5, $this->mutator->mutate(10, $callable));
    }

    /** @test */
    public function it_accepts_additional_parameters()
    {
        $callable = 'substr:2,3';

        $this->assertEquals('ick', $this->mutator->mutate('quick red fox', $callable));
    }

    /** @test */
    public function it_accepts_pipe_separated_multiple_methods()
    {
        $callable = 'substr:0,5|strtoupper';

        $this->assertEquals('QUICK', $this->mutator->mutate('quick red fox', $callable));
    }

    /** @test */
    public function it_accepts_array_of_methods()
    {
        $callable = [
            'substr:5,10', // ' red fox'
            'strtoupper',  // ' RED FOX'
            'clip:4',      // ' RED '
            'Sofa\Eloquence\Tests\MutatorDummyInstantiable@repeat:3',
        ];

        $this->assertEquals(' RED RED RED', $this->mutator->mutate('quick red fox', $callable));
    }

    /** @test */
    public function it_can_be_extended_with_macros()
    {
        $callable = [
            'custom_uppercase',
        ];

        $this->mutator->macro('custom_uppercase', function ($value) {
            return strtoupper($value);
        });

        $this->assertEquals('QUICK RED FOX', $this->mutator->mutate('quick red fox', $callable));
    }

    /**
     * @test
     * @dataProvider wrongCallables
     */
    public function wrong_callable($callable)
    {
        $this->expectException(LogicException::class);
        $this->mutator->mutate('quick red fox', $callable);
    }

    public function wrongCallables()
    {
        return [
            ['jibberrish!@#$%^&*('],
            ['StdClass@jibberrish'],
            ['wrong_function'],
            ['Sofa\Eloquence\Tests\MutatorDummyNotInstantiable@repeat:3'],
            ['Sofa\Eloquence\Tests\MutatorDummyRequiredArgs@repeat:3'],
            ['Sofa\Eloquence\Tests\MutatorDummyInstantiable@protectedMethod'],
        ];
    }
}

class MutatorStub extends Mutator
{
    public function clip($string, $length = 5)
    {
        return substr($string, 0, $length);
    }
}

class MutatorDummyInstantiable
{
    public static function multiply($value, $multiplier = 2)
    {
        return $value * $multiplier;
    }

    public function divide($value, $divisor = 2)
    {
        return $value / $divisor;
    }

    public function repeat($value, $times = 2)
    {
        return str_repeat($value, $times);
    }

    protected function protectedMethod($value)
    {
        return $value;
    }
}

abstract class MutatorDummyNotInstantiable
{
    public function repeat($value, $multiplier = 2)
    {
        return $value * $multiplier;
    }
}

class MutatorDummyRequiredArgs
{
    public function __construct($arg)
    {
    }

    public function repeat($value, $multiplier = 2)
    {
        return $value * $multiplier;
    }
}
