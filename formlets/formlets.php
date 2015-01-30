<?php
/******************************************************************************
 * Copyright (c) 2014 Richard Klees <richard.klees@rwth-aachen.de>
 */

require_once("checking.php");
require_once("lib.php");
require_once("values.php");
require_once("builders.php");
require_once("collectors.php");
require_once("namesource.php");

abstract class Formlet implements IFormlet {
    /* Build a builder and collector from the formlet and also return the 
     * updated name source.
     */
    public abstract function instantiate(NameSource $name_source);
    
    /* Combine this formlet with another formlet. Yields a new formlet. */
    final public function cmb(IFormlet $other) {
        return new CombinedFormlets($this, $other);
    }

    /* Get a new formlet with an additional check of a predicate on the input
     * to the formlet and an error message for the case the predicate fails.
     */
    final public function satisfies(IValue $predicate, $error) {
        return $this->mapBC( _id()
                           , _fn( function ($collector) use ($predicate, $error) {
                                return $collector->satisfies($predicate, $error);
                           }));
    }

    /* Map a function over the input value. */
    final public function map(IValue $transformation) {
        return $this->mapBC( _id()
                            , _fn( function($collector) use ($transformation) {
                                return $collector->map($transformation);
                            }));
    }

    /* Wrap a function around the collector. */
    final public function wrapCollector(IValue $wrapper) {
        return $this->mapBC( _id()
                           , _fn( function($collector) use ($wrapper) {
                                return $collector->wrap($wrapper);
                           }));
    }

    /* Replace the collector. */
    final public function replaceCollector(Collector $collector) {
        return $this->mapBC( _id()
                           , _fn( function($_) use ($collector) {
                                return $collector;
                           }));
    }

    /* Map a function over the build HTML. */
    final public function mapHTML(FunctionValue $transformation) {
        return $this->mapBC( _fn( function($builder) use ($transformation) {
                                return $builder->map($transformation);
                            })
                            , _id()
                            );
    }

    /* Map a function over the builder and collector. */
    final public function mapBC( FunctionValue $transform_builder
                                , FunctionValue $transform_collector ) {
        return new MappedFormlet($this, $transform_builder, $transform_collector);
    }
}


/* A PureFormlet collects a constant value and buildes to an empty string. */
class PureFormlet extends Formlet {
    private $_value; // mixes

    public function __construct(Value $value) {
        $this->_value = $value;
    }

    public function instantiate(NameSource $name_source) {
        return array
            ( "builder"    => new TextBuilder("")
            , "collector"   => new ConstCollector($this->_value)
            , "name_source" => $name_source
            );
    }
}

function _pure(Value $value) {
    return new PureFormlet($value); 
}


/* A combined formlets glues to formlets together to a new one. */ 
class CombinedFormlets extends Formlet {
    private $_l; // Formlet
    private $_r; // Formlet

    public function __construct(Formlet $left, Formlet $right) {
        $this->_l = $left;
        $this->_r = $right;
    }

    public function instantiate(NameSource $name_source) {
        $l = $this->_l->instantiate($name_source);
        $r = $this->_r->instantiate($l["name_source"]);
        $collector = combineCollectors($l["collector"], $r["collector"]);
        return array
            ( "builder"    => new CombinedBuilder($l["builder"], $r["builder"])
            , "collector"   => $collector
            , "name_source" => $r["name_source"]
            );
    }
}


/* A formlet where a function is applied to buiid builder and collector. */
class MappedFormlet extends Formlet {
    private $_formlet; // Formlet
    private $_transform_builder; // FunctionValue 
    private $_transform_collector; // FunctionValue 
    
    public function __construct( Formlet $formlet
                               , FunctionValue $transform_builder
                               , FunctionValue $transform_collector ) {
        guardHasArity($transform_builder, 1);
        guardHasArity($transform_collector, 1);
        $this->_formlet = $formlet;
        $this->_transform_builder = $transform_builder; 
        $this->_transform_collector = $transform_collector;
    }

    public function instantiate(NameSource $name_source) {
        $fmlt = $this->_formlet->instantiate($name_source);
        $b = $this->_transform_builder
                ->apply(_val($fmlt["builder"]))
                ->get();
        $c = $this->_transform_collector
                ->apply(_val($fmlt["collector"]))
                ->get();
        return array( "builder"    => $b
                    , "collector"   => $c
                    , "name_source" => $fmlt["name_source"]
                    );
    }
}


/* A formlet collecting nothing and building a constant string. */
class TextFormlet extends Formlet {
    private $_content; // string

    public function __construct($content) { 
        guardIsString($content);
        $this->_content = $content;
    }

    public function instantiate(NameSource $name_source) {
        return array
            ( "builder"    => new TextBuilder($this->_content)
            , "collector"   => new NullaryCollector()
            , "name_source" => $name_source
            );
    }
}

function _text($content) {
    return new TextFormlet($content);
}


/* A simple html input. */
class InputFormlet extends Formlet implements TagBuilderCallbacks {
    protected $_attributes;

    public function __construct($attributes) {
        guardEachAndKeys($attributes, "guardIsString", "guardIsString");
        $this->_attributes = $attributes;
    }

    public function instantiate(NameSource $name_source) {
        $res = $name_source->getNameAndNext();
        return array
            ( "builder"    => new TagBuilder( "input", $this, $res["name"])
            , "collector"   => new AnyCollector($res["name"])
            , "name_source" => $res["name_source"]
            );
    }

    public function getContent(RenderDict $dict, $name) {
        return null; 
    }

    public function getAttributes(RenderDict $dict, $name) {
        $attributes = id($this->_attributes);
        $attributes["name"] = $name; 
        return $attributes; 
    }
}

function _input($type, $attributes = array()) {
    guardIsString($type);
    $attributes["type"] = $type;
    return new InputFormlet($attributes);
}

/* A formlet to input some text in an area. */
class TextAreaFormlet extends Formlet implements TagBuilderCallbacks {
    protected $_attributes; // string

    public function __construct($attributes = null) {
        if ($attributes !== null)
            guardIsArray($attributes);
        $this->_attributes = $attributes; 
    }

    public function instantiate(NameSource $name_source) {
        $res = $name_source->getNameAndNext();
        return array
            ( "builder"    => new TagBuilder( "textarea", $this, $res["name"] )
            , "collector"   => new AnyCollector($res["name"])
            , "name_source" => $res["name_source"]
            );
    }

    public function getContent(RenderDict $dict, $name) {
        return html_text("");
    }

    public function getAttributes(RenderDict $dict, $name) {
        $attributes = id($this->_attributes);
        $attributes["name"] = $name; 
        return $attributes; 
    }
}

function _textarea_raw($attributes = null) {
    return new TextAreaFormlet($attributes);
}


function _text_input($default = null, $attributes = null) {
    guardIfNotNull($default, "guardIsString");
    return _input("text", $attributes)
        // Only accept string inputs
        //->satisfies(_fn("is_string"), "Input is no string.")
        // Set value by input or given value if there is no input. 
        ->mapHTML(_fn(function ($dict, $html) use ($default) {
            $name = $html->attribute("name");

            $value = $dict->value($name);
            if ($value === null)
                $value == $default;
            if ($value !== null)
                $html = $html->attribute("value", $value);

            return $html;
        }));
}


function _textarea($default = null, $attributes = null) {
    guardIfNotNull($default, "guardIsString");
    return _textarea_raw($attributes)
        ->satisfies(_fn("is_string"), "Input is no string.")
        ->mapHTML(_fn(function ($dict, $html) use ($default) {
            $name = $html->attribute("name");
            
            $value = $dict->value($name);
            if ($value === null)
                $value = $default;
            if ($value !== null)
                $html = $html->content(html_text($value));

            return $html;
        }));
}


function _checkbox($default = false, $attributes = null) {
    guardIsBool($default);
    return _input("checkbox", $attributes)
        ->wrapCollector(_fn(function($collector, $inp) {
            // We don't really need the value, we just
            // have to check weather it is there.
            try {
                $collector->collect($inp);
                return true;
            }
            catch (MissingInputError $e) {
                return false;
            }
        }))
        ->mapHTML(_fn(function ($dict, $html) use ($default) {
            $name = $html->attribute("name");

            if ($dict->isEmpty())
                $value = $default;
            else
                $value = $dict->value($name) !== null;
            if ($value)
                return $html->attribute("checked", "checked");
            return $html;
        })); 
} 

function _submit($value, $attributes = array(), $collects = false) {
    $attributes["value"] = $value;
    $input = _input("submit", $attributes);

    if ($collects) {
        return $input->wrapCollector(_fn(function($collector, $inp) {
            try {
                $collector->collect($inp);
                return true;
            } 
            catch (MissingInputError $e) {
                return false;
            }
        }));
    }
    else {
        return $input->replaceCollector( new NullaryCollector() );
    }
}

/* A formlet that wraps other formlets in a field set */
function _fieldset($legend, Formlet $formlet
                  , $attributes = array(), $legend_attributes = array()) {
    return $formlet
        ->mapHTML(_fn(function ($dict, $html) 
                      use ($legend, $attributes, $legend_attributes) {

            return html_tag("fieldset", $attributes, 
                        html_concat(
                              html_tag("legend", $legend_attributes, 
                                html_text($legend))
                            , $html
                        )
                    );

        }));
} 

/**
 * Apply $fn to all tags that have a name attribute in $html.
 *
 * This transforms the html in place, that is _mute_ it. If $fn returns some 
 * value except for null, it will only be applied to the first named tag.
 */
function html_apply_to_depth_first_name(HTML $html, FunctionValue $fn) {
    return $html->depthFirst(
                        _fn(function($html) {
                            return $html instanceof HTMLTag
                                && $html->attribute("name");
                        }),
                        $fn);
}

/**
 * Returns the name of the first tag with name attribute in $html.
 */
function html_get_depth_first_name(HTML $html) {
    return html_apply_to_depth_first_name($html,
                        _fn(function($html) {
                            return $html->attribute("name");
                        }));
}

function _with_label($label, Formlet $other) {
    return $other->mapHTML(_fn( function ($_, $html) use ($label) {
        // use inputs name as id, as it is unique
        $name = html_get_depth_first_name($html);
        if ($name === null) {
            throw new Exception("_with_label applied to un-named Formlet.");
        }

        // This applies the transformation in place!
        html_apply_to_depth_first_name($html, _fn(function($html) use ($name) {
            $html->attribute("id", $name);
            return true;
        }));

        return html_concat(
                    html_tag("label", array("for" => $name), html_text($label)),
                    $html
                );
    }));   
}

function _with_errors(Formlet $other) {
    return $other->mapHTML(_fn(function ($dict, $html) {
        $name = html_get_depth_first_name($html);
        if ($name === null) {
            throw new Exception("_with_errors applied to un-named Formlet.");
        }

        $errors = $dict->errors($name);
        if ($errors === null)
            return $html;

        foreach ($errors as $error) {
            $html = html_concat
                        ( $html
                        , html_tag("span", array("class" => "error"), html_text($error))
                        );
        }

        return $html;
    }));
}

function id($val) {
    return $val;
}

function _id() {
    static $fn = null;
    if ($fn === null) {
        $fn = _fn("id");
    }
    return $fn;
}

?>
