<?php
/**
 * Copyright (c) 2007-2008 Martin Jansen
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Test suite for the URI_Templates class
 *
 * @author Martin Jansen <mj@php.net>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD
 */

error_reporting(E_ALL);

// Call URI_TemplateTest::main() if executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "URI_TemplateTest::main");
}

require_once "PHPUnit/Framework/TestCase.php";
require_once "PHPUnit/Framework/TestSuite.php";

require_once "URI/Template.php";

/**
 * Test class for URI_Template.
 *
 * @author Martin Jansen <mj@php.net>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD
 */
class URI_TemplateTest extends PHPUnit_Framework_TestCase
{

    /**
     * Runs the test methods of this class.
     */
    public static function main() {
        include_once "PHPUnit/TextUI/TestRunner.php";

        $suite  = new PHPUnit_Framework_TestSuite("URI_TemplateTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    public function testSubstitute() {
        $tests = array(
            # (template, values, expected)
            array("foo",        array(),                    ""),
            array("foo",        array("foo" =>  "barney"),  "barney"),
            array("foo=wilma",  array(),                    "wilma"),
            array("foo=wilma",  array("foo" =>  "barney"),  "barney"),
        );

        self::runnerHelper($tests);
    }
    
    public function testSubstituteSansURLEncoding() {
        $t = new URI_Template("{foo}");
        $result = $t->substitute(array("foo" => "%s"), false);
        self::assertEquals("%s", $result);

        $result = $t->substitute(array("foo" => "%s"), true);
        self::assertEquals("%25s", $result);

        $result = $t->substitute(array("foo" => "%s"));
        self::assertEquals("%25s", $result);
    }

    public function testGetTemplateVariables() {
        $tests = array(
            "/{-suffix|/|a}{-opt|data|points}{-neg|@|a}{-prefix|#|b}" => array("a", "points", "b"),
            "relative/{reserved}/"                                    => array("reserved"),
            "http://example.org/{foo=%25}/"                           => array("foo"),
            "http://example.org/?{-join|&|a,data}"                    => array("a", "data"),
            "http://example.org/?d={-list|,|points}&{-join|&|a,b}"    => array("points", "a", "b"),
            "http://example.org/?d={-list|,|list0}&{-join|&|foo}"     => array("list0", "foo"),
            "http://example.org/?d={-list|&d=|points}"                => array("points"),
            "http://example.org/{a}{b}/{a_b}"                         => array("a", "b", "a_b"),
            "http://example.org/{a}{-prefix|/-/|a}/"                  => array("a"),
            );

        $i = 0;
        foreach ($tests as $uri => $expected) {
            $t = new URI_Template($uri);
            $result = $t->getTemplateVariables();

            self::assertEquals($expected, $result, sprintf(
                __FUNCTION__ . ": test case #%d [%s != %s]", 
                ++$i, $expected, $result
            ));
        }
    }

    public function testSuffix() {
        $tests = array(
            array("-suffix|/|foo",          array(),                    ""),
            array("-suffix|#|foo=wilma",    array(),                    "wilma#"),
            array("-suffix|&?|foo=wilma",   array("foo" =>  "barney"),  "barney&?"),
            array("-suffix|&|foo",          array("foo" => array("wilma", "barney")), "wilma&barney&")
        );

        self::runnerHelper($tests);
    }

    public function testList() {
        $tests = array(
            array("-list|/|foo",        array(),                            ""),
            array("-list|/|foo",        array("foo" =>  array("a", "b")),   "a/b"),
            array("-list||foo",         array("foo" =>  array("a", "b")),   "ab"),
            array("-list|/|foo",        array("foo" =>  array("a")),        "a"),
            array("-list|/|foo",        array("foo" =>  array()),           ""),
        );

        self::runnerHelper($tests);
    }

    public function testJoin() {
        $tests = array(
            array("-join|/|foo",                    array(),                    ""),
            array("-join|/|foo,bar",                array(),                    ""),
            array("-join|&|q,num",                  array(),                    ""),
            array("-join|#|foo=wilma",              array(),                    "foo=wilma"),
            array("-join|#|foo=wilma,bar",          array(),                    "foo=wilma"),
            array("-join|#|foo=wilma,bar=barney",   array(),                    "bar=barney#foo=wilma"),
            array("-join|&?|foo=wilma",             array("foo" =>  "barney"),  "foo=barney"),
        );

        self::runnerHelper($tests);
    }

    public function testPrefix() {
        $tests = array(
            array("-prefix|&|foo",       array(),                 ""),
            array("-prefix|&|foo=wilma", array(),                 "&wilma"),
            array("-prefix||foo=wilma", array(),                 "wilma"),
            array("-prefix|&|foo=wilma", array("foo" =>  "barney"),  "&barney"),
            array("-prefix|&|foo", array("foo" => array("wilma", "barney")), "&wilma&barney")
        );

        self::runnerHelper($tests);
    }

    public function testOpt() {
        $tests = array(
            array("-opt|&|foo",       array(),                      ""),
            array("-opt|&|foo",       array("foo" =>  "fred"),         "&"),
            array("-opt|&|foo",       array("foo" =>  array()),             ""),
            array("-opt|&|foo",       array("foo" =>  array("a")),          "&"),
            array("-opt|&|foo,bar",   array("foo" =>  array("a")),          "&"),
            array("-opt|&|foo,bar",   array("bar" =>  "a"),         "&"),
            array("-opt|&|foo,bar",   array(),                      ""),
        );

        self::runnerHelper($tests);
    }

    public function testNeg() {
        $tests = array(
            array("-neg|&|foo",       array(),                      "&"),
            array("-neg|&|foo",       array("foo" =>  "fred"),      ""),
            array("-neg|&|foo",       array("foo" =>  array()),     "&"),
            array("-neg|&|foo",       array("foo" =>  array("a")),  ""),
            array("-neg|&|foo,bar",   array("bar" =>  "a"),         ""),
            array("-neg|&|foo,bar",   array("bar" =>  array()),     "&"),
        );

        self::runnerHelper($tests);
    }

    public function testSpecials() {
        $tests = array(
            array("foo",                array("foo" =>  " "),                       "%20"),
            array("-list|&|foo",    array("foo" =>  array("&", "&", "|", "_")), "%26&%26&%7C&_"),
        );

        self::runnerHelper($tests);
    }

    public function testURITemplate() {
        $t = new URI_Template("http://example.org/news/{id}/");
        self::assertEquals("http://example.org/news/joe/", $t->substitute(array("id" => "joe")));

        $t = new URI_Template("http://www.google.com/notebook/feeds/{userID}{-prefix|/notebooks/|notebookID}{-opt|/-/|categories}{-list|/|categories}?{-join|&|updated-min,updated-max,alt,start-index,max-results,entryID,orderby}");
        self::assertEquals("http://www.google.com/notebook/feeds/joe?", $t->substitute(array("userID" => "joe")));

        self::assertEquals("http://www.google.com/notebook/feeds/joe/-/A%7C-B/-C?start-index=10",
        $t->substitute(array("userID" => "joe", "categories" => array("A|-B", "-C"), "start-index" => "10")));

        /* Source: IETF Draft 03 */
        $values = array("bar" => "fred", "baz" => "10,20,30", "qux" => array(10, 20, 30),
                        "corge" => array(), "grault" => " ", "garply" => "a/b/c",
                        "waldo" => "ben & jerrys", "fred" => array("fred", "", "wilma"),
                        "1-a_b.c" => 200);

        $tests = array(
            "http://example.org/?q={bar}" => "http://example.org/?q=fred",
            "/{xyzzy}" => "/",
            "http://example.org/?{-join|&|bar,xyzzy,baz}" => "http://example.org/?bar=fred&baz=10%2C20%2C30",
            "http://example.org/?d={-list|,|qux}" => "http://example.org/?d=10,20,30",
            "http://example.org/?d={-list|&d=|qux}" => "http://example.org/?d=10&d=20&d=30",
            "http://example.org/{bar}{bar}/{garply}" => "http://example.org/fredfred/a%2Fb%2Fc",
            "http://example.org/{bar}{-prefix|/|fred}" => "http://example.org/fred/fred//wilma",
            "../{waldo}/" => "../ben%20%26%20jerrys/",
            "telnet:192.0.2.16{-opt|:80|grault}" => "telnet:192.0.2.16:80",
            ":{1-a_b.c}:" => ":200:",
            );
            
        foreach ($tests as $key => $value) {
            $t = new URI_Template($key);
            self::assertEquals($value, $t->substitute($values));
        }

        /* Source: IETF Draft 02 */
        $values = array("a" => "foo", "b" => "bar", "data" => "10,20,30",
                        "points" => array(10, 20, 30), "list0" => array(),
                        "str0" => "", "reserved" => ":/?#[]@!$&'()*+,;=",
                        "a_b" => "baz");

        $tests = array(
            "/{-suffix|/|a}{-opt|data|points}{-neg|@|a}{-prefix|#|b}" => "/foo/data#bar",
            "relative/{reserved}/" => "relative/%3A%2F%3F%23%5B%5D%40%21%24%26%27%28%29%2A%2B%2C%3B%3D/",
            "http://example.org/{foo=%25}/" => "http://example.org/%25/",
            "http://example.org/?{-join|&|a,data}" => "http://example.org/?a=foo&data=10%2C20%2C30",
            "http://example.org/?d={-list|,|points}&{-join|&|a,b}" => "http://example.org/?d=10,20,30&a=foo&b=bar",
            "http://example.org/?d={-list|,|list0}&{-join|&|foo}" => "http://example.org/?d=&",
            "http://example.org/?d={-list|&d=|points}" => "http://example.org/?d=10&d=20&d=30",
            "http://example.org/{a}{b}/{a_b}" => "http://example.org/foobar/baz",
            "http://example.org/{a}{-prefix|/-/|a}/" => "http://example.org/foo/-/foo/",
            );

        foreach ($tests as $key => $value) {
            $t = new URI_Template($key);
            self::assertEquals($value, $t->substitute($values));
        }
    }

    private static function runnerHelper($tests) {
        $i = 0;
        foreach ($tests as $test) {
            list($template, $values, $expected) = $test;
            $t = new URI_Template("{" . $template . "}");
            $result = $t->substitute($values);
            self::assertEquals($expected, $result, sprintf("Test case #%d [%s != %s]", ++$i, $expected, $result));
        }
    }
}

