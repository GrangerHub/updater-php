<?php
class Semver
{
    private $major;
    private $minor;
    private $patch;
    private $prerelease;
    private $metadata;

    public function __construct($str='0.0.0')
    {
        $this->parse($str);
    }

    public function __toString()
    {
        $str = $this->major . '.' . $this->minor . '.' . $this->patch;

        if (!empty($this->prerelease)) {
            $str .= '-' . $this->prerelease;
        }

        if (!empty($this->metadata)) {
            $str .= '+' . $this->metadata;
        }

        return $str;
    }

    private function parse($str)
    {
        $re = '/^[Vv]?(\\d+)(?:\\.(\\d+)(?:\\.(\\d+))?)?(?:-([^+]+))?'
              . '(?:\\+(.+))?$/';

        preg_match($re, $str, $m);
        $this->major = isset($m[1]) ? (int)$m[1] : 0;
        $this->minor = isset($m[2]) ? (int)$m[2] : 0;
        $this->patch = isset($m[3]) ? (int)$m[3] : 0;
        $this->prerelease = isset($m[4]) ? $m[4] : '';
        $this->metadata = isset($m[5]) ? $m[5] : '';
    }

    private function compare($other)
    {
        if ($this->major < $other->major) {
            return -1;
        } elseif ($this->major > $other->major) {
            return 1;
        }

        if ($this->minor < $other->minor) {
            return -1;
        } elseif ($this->minor > $other->minor) {
            return 1;
        }

        if ($this->patch < $other->patch) {
            return -1;
        } elseif ($this->patch > $other->patch) {
            return 1;
        }

        if (!empty($this->prerelease) && empty($other->prerelease)) {
            return -1;
        } elseif (empty($this->prerelease) && !empty($other->prerelease)) {
            return 1;
        } elseif (!empty($this->prerelease) && !empty($other->prerelease)) {
            $parts1 = explode('.', $this->prerelease);
            $parts2 = explode('.', $other->prerelease);

            for ($i = 0; $i < min(count($parts1), count($parts2)); $i++) {
                $s1 = $parts1[$i];
                $s2 = $parts2[$i];

                if (is_numeric($s1) && !is_numeric($s2)) {
                    return -1;
                } elseif (!is_numeric($s1) && is_numeric($s2)) {
                    return 1;
                } elseif (is_numeric($s1) && is_numeric($s2)) {
                    $i1 = (int)$s1;
                    $i2 = (int)$s2;

                    if ($i1 < $i2) {
                        return -1;
                    } elseif ($i1 > $i2) {
                        return 1;
                    }
                } else {
                    $result = strcmp($s1, $s2);
                    if ($result != 0) {
                        return $result;
                    }
                }
            }
            if (count($parts2) > $i) {
                return -1;
            } elseif (count($parts1) > $i) {
                return 1;
            }
        }
        return 0;
    }

    public static function equal($v1, $v2)
    {
        return $v1->compare($v2) == 0;
    }

    public static function notEqual($v1, $v2)
    {
        return $v1->compare($v2) != 0;
    }

    public static function lessThan($v1, $v2)
    {
        return $v1->compare($v2) < 0;
    }

    public static function lessThanOrEqual($v1, $v2)
    {
        return $v1->compare($v2) <= 0;
    }

    public static function greaterThan($v1, $v2)
    {
        return $v1->compare($v2) > 0;
    }

    public static function greaterThanOrEqual($v1, $v2)
    {
        return $v1->compare($v2) >= 0;
    }
}
