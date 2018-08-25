<?php

declare(strict_types=1);

namespace Sandertv\Marshal;

class DataProcessor{

	/**
	 * TAG_MARSHAL is the tag used to define a different name for marshalling. '-' may be used to completely ignore a
	 * property when marshalling/unmarshalling.
	 */
	public const TAG_MARSHAL = "marshal";

	/**
	 * put iterates through $data and sets values in $obj of properties that match keys in $data. The
	 * properties are only set if the types match with the current values of $obj.
	 *
	 * @param \object $obj
	 * @param array   $data
	 */
	public function put(object $obj, array $data) : void{
		foreach($data as $key => $value){
			// Cast to strings in case the object is actually an array casted to an object with numeric keys.
			$key = $this->scanFor($obj, (string) $key);
			if($key === "-"){
				continue;
			}
			if(!isset($obj->{(string) $key})){
				$obj->{(string) $key} = null;
			}
			$this->handleProperty($obj, $key, $value);
		}
	}

	/**
	 * scanFor looks for a property with the given name and returns it if found. If not found, it scans through all
	 * properties in the object provided and checks if the tag 'marshal' is equal to $propertyName.
	 *
	 * @param \object $obj
	 * @param string  $propertyName
	 *
	 * @return string
	 */
	private function scanFor(object $obj, string $propertyName) : string{
		$refl = new \ReflectionObject($obj);
		do{
			if($refl->hasProperty($propertyName)){
				$tags = self::parseDocComment($refl->getProperty($propertyName)->getDocComment());
				if(isset($tags[self::TAG_MARSHAL])){
					if($tags[self::TAG_MARSHAL] !== $propertyName){
						// The marshal tag is not equal to $propertyName, so this property expects the value of a
						// different key.
						break;
					}
				}
				// No need to scan if we can find a property with the same name right away, and it has no additional
				// marshaltag that tells us it wants a different key.
				return $propertyName;
			}
		}while(false);

		foreach($refl->getProperties() as $property){
			if($property->getDocComment() === false){
				// No marshal
				continue;
			}
			$tags = self::parseDocComment($property->getDocComment());
			if(isset($tags[self::TAG_MARSHAL])){
				if($tags[self::TAG_MARSHAL] === $propertyName){
					// The marshal tag is equal to $propertyName, so we return the name of the property that has this
					// tag, which gets its value set later.
					return $property->name;
				}
			}
		}

		return "-";
	}

	/**
	 * parseDocComment parses the documentation from a property.
	 *
	 * @param string $docComment
	 *
	 * @return array
	 */
	public static function parseDocComment(string $docComment) : array{
		preg_match_all('/(*ANYCRLF)^[\t ]*\* @([a-zA-Z]+)(?:[\t ]+(.+))?[\t ]*$/m', $docComment, $matches);

		return array_combine($matches[1], $matches[2]);
	}

	/**
	 * handleProperty handles a property in $obj with index $key. The value $value is the value the property should
	 * obtain, if their types are compatible.
	 *
	 * @param \object $obj
	 * @param string  $key
	 * @param mixed   $value
	 */
	private function handleProperty(object $obj, string $key, $value) : void{
		if(is_object($obj->{$key})){
			if(!is_array($value)){
				// The property value was an object, but we didn't have an array as config value. We can't unmarshal
				// this at all, so just continue.
				return;
			}
			// Recursively iterate through the objects.
			$this->put($obj->{$key}, $value);

			return;
		}elseif(is_array($obj->{$key})){
			// We temporarily cast this array to an stdClass so we can use the put method for it.
			$v = (object) $obj->{$key};
			$this->put($v, $value);
			// Cast it back to an array to the types remain consistent.
			$obj->{$key} = (array) $v;

			return;
		}

		// Only set the value if the current value either matches the one in the configuration, or if the current
		// value is null.
		if(gettype($obj->{$key}) === gettype($value) || $obj->{$key} === null){
			$obj->{$key} = $value;
		}
	}
}