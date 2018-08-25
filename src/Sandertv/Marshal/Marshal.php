<?php

declare(strict_types=1);

namespace Sandertv\Marshal;

class Marshal{

	/**
	 * json encodes $obj to JSON and returns it, using the provided $options.
	 *
	 * @param \object $obj to encode to JSON.
	 * @param int     $options for changing the output JSON.
	 * @param int     $depth maximum nesting depth, must be greater than 0.
	 *
	 * @return string of JSON.
	 */
	public static function json(object $obj, int $options = 0, int $depth = 512) : string{
		return json_encode(self::processMarshalTags($obj), $options, $depth);
	}

	/**
	 * jsonFile encodes $obj to JSON and returns it using the provided $options. The output is written to $file.
	 *
	 * @param string  $file to write the JSON string to.
	 * @param \object $obj to encode to JSON.
	 * @param int     $options for changing the output JSON.
	 * @param int     $depth maximum nesting depth, must be greater than 0.
	 */
	public static function jsonFile(string $file, object $obj, int $options = 0, int $depth = 512) : void{
		file_put_contents($file, self::json($obj, $options, $depth));
	}

	/**
	 * yaml encodes $obj to YAML and returns it, using $encoding and $linebreak according to the specifications of YAML.
	 *
	 * @param \object $obj to encode to YAML.
	 * @param int     $encoding to use to encode $obj.
	 * @param int     $linebreak to use for newlines.
	 *
	 * @return string of YAML encoded data.
	 */
	public static function yaml(object $obj, int $encoding = YAML_ANY_ENCODING, int $linebreak = YAML_ANY_BREAK) : string{
		// YAML, unlike JSON, cannot write PHP objects properly, so we first change all objects to arrays.
		return yaml_emit(self::objToArray(self::processMarshalTags($obj)), $encoding, $linebreak);
	}

	/**
	 * yaml encodes $obj to YAML and writes it to $file, using $encoding and $linebreak according to the specifications
	 * of YAML.
	 *
	 * @param string  $file to write the encoded YAML to.
	 * @param \object $obj to encode to YAML.
	 * @param int     $encoding to use to encode $obj.
	 * @param int     $linebreak to use for newlines.
	 */
	public static function yamlFile(string $file, object $obj, int $encoding = YAML_ANY_ENCODING, int $linebreak = YAML_ANY_BREAK) : void{
		file_put_contents($file, self::yaml($obj, $encoding, $linebreak));
	}

	/**
	 * objToArray converts an object to an array, so that functions such as yaml_emit can use them.
	 *
	 * @param mixed $obj
	 *
	 * @return mixed
	 */
	private static function objToArray($obj){
		if(is_object($obj)){
			$obj = get_object_vars($obj);
		}
		if(is_array($obj)){
			return array_map('self::objToArray', $obj);
		}

		return $obj;
	}

	/**
	 * @param \object $obj
	 *
	 * @return \object
	 */
	private static function processMarshalTags(object $obj) : object{
		$c = clone($obj);
		$refl = new \ReflectionObject($c);
		foreach((array) $c as $propertyName => $value){
			unset($c->{$propertyName});
			// Cast the property name to a string because an array with numeric indexes casted to an object may have
			// integer keys, which causes issues.
			$tags = DataProcessor::parseDocComment($refl->getProperty((string) $propertyName)->getDocComment());
			if(isset($tags[DataProcessor::TAG_MARSHAL])){
				$propertyName = $tags[DataProcessor::TAG_MARSHAL];
				if($propertyName === "-"){
					// Leave properties with '-' as marshal name unset and don't set the tag. We created a clone of the
					// object, so we do not need to set it back.
					continue;
				}
			}
			if(is_object($value)){
				$value = self::processMarshalTags($value);
			}elseif(is_array($value)){
				// Cast the array to an object and process it recursively in case this array contained any objects, and
				// cast it back to an array immediately after.
				$value = (array) self::processMarshalTags((object) $value);
			}
			$c->{$propertyName} = $value;
		}

		return $c;
	}
}