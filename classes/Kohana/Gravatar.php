<?php

/**
 * Written by Andreas Glaser (http://andreas.glaser.me)
 *
 * Redesigned API and implementation by David Doran (daviddoran.com)
 */
defined('SYSPATH') or die('No direct script access.');

class Kohana_Gravatar
{
    const G  = "g";
    const PG = "pg";
    const R  = "r";
    const X  = "x";

    const E404 = "404"; //return HTTP 404 error if email not found
    const MM = "mm"; //mystery-man; a simple, cartoon-style silhouetted outline of a person
    const IDENTICON = "identicon"; //a geometric pattern based on an email hash
    const MONSTERID = "monsterid"; //a generated 'monster' with different colors, faces, etc
    const WAVATAR = "wavatar"; //generated faces with differing features and backgrounds
    const RETRO = "retro"; //8-bit arcade-style pixelated faces
    const BLANK = "blank"; //a transparent PNG image

    /**
     * Content rating
     *
     * @var string
     */
    protected $rating = null;

    /**
     * Image size
     *
     * @var int
     */
    protected $size = null;

    /**
     * Default image type.
     *
     * @var mixed
     */
    protected $default_image = null;

    /**
     * If default image shall be shown
     * even if user the has an gravatar profile.
     *
     * @var boolean
     */
    protected $force_default = false;

    /**
     * Whether or not to use HTTPS
     *
     * @var boolean
     */
    protected $https = false;

    /**
     * Download destination (local directory)
     *
     * @var string
     */
    protected $destination = null;

    /**
     * Returns new \Gravatar object
     *
     * @param array $params
     * @return \Gravatar
     */
    public static function factory(array $params = array())
    {
        return new Gravatar($params);
    }

    /**
     * Helps to load default settings passed by array.
     *
     * @param array $params
     * @return \Kohana_Gravatar
     */
    public function setup(array $params)
    {
        // destination
        if (isset($params['destination']))
        {
            $this->destination($params['destination']);
        }
        else
        {
            $this->destination(sys_get_temp_dir());
        }

        // size
        if (isset($params['size']))
        {
            $this->size($params['size']);
        }

        // https
        if (isset($params['https']))
        {
            $this->https($params['https']);
        }

        // rating
        if (isset($params['rating']))
        {
            $this->rating($params['rating']);
        }

        // default image
        if (isset($params['default']))
        {
            $this->default_image($params['default']);
        }

        // force default
        if (isset($params['default_force']))
        {
            $this->force_default($params['default_force']);
        }

        return $this;
    }

    /**
     * Public function returning $this->url_make();
     *
     * @param $email
     * @return string
     */
    public function url($email)
    {
        return $this->url_make($email);
    }

    /**
     * Returns html code e.g.
     * <img src="htp://someurl" />
     *
     * @param string $email
     * @param array $attributes
     * @param boolean $protocol
     * @param boolean $index
     * @return string
     */
    public function image($email, array $attributes = null, $protocol = null, $index = false)
    {
        // set auto attributes
        $attributes_auto = array(
            'width' => $this->size,
            'height' => $this->size
        );

        // merge attributes
        $attributes = Arr::merge($attributes_auto, (array) $attributes);

        // return html
        return HTML::image($this->url_make($email), $attributes, $protocol, $index);
    }

    /**
     * Downloads gravatar to location on server. Defaults to tmp directory.
     *
     * @param string $email
     * @throws \Kohana_Exception
     * @return \stdClass
     */
    public function download($email)
    {
        // trim leading/trailing white spaces
        $email = trim($email);

        // force lowercase
        $email = strtolower($email);

        // make sure passed email address is valid
        if (!Valid::email($email))
        {
            $this->exception('Invalid email address passed');
        }

        // make url
        $url = $this->url_make($email);

        try
        {
            $headers = get_headers($url, 1);
        } catch (ErrorException $e)
        {
            if ($e->getCode() === 2)
            {
                $this->exception('URL does not seem to exist', array(), 200);
            }
        }

        $valid_content_types = array(
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/gif'
        );

        // make sure content type exists
        if (!isset($headers['Content-Type']))
        {
            $this->exception('Download - Content-Type not found', array(), 300);
        }

        // make sure content type is valid
        if (!in_array($headers['Content-Type'], $valid_content_types))
        {
            $this->exception('Download - Content-Type invalid', array(), 305);
        }

        // make sure content disposition exist
        if (isset($headers['Content-Disposition']))
        {
            preg_match('~filename="(.*)"~', $headers['Content-Disposition'], $matches);

            if (!isset($matches[1]))
            {
                $this->exception('Download - Filename not found', array(), 315);
            }

            $filename = $matches[1];
        } else
        {
            $filename = md5($url) . '.' . File::ext_by_mime($headers['Content-Type']);
        }

        try
        {
            file_put_contents($this->destination . $filename, file_get_contents($url));
        } catch (ErrorException $e)
        {
            $this->exception('Download - File could not been downloaded', array(), 400);
        }

        $result = new stdClass;
        $result->filename = $filename;
        $result->extension = File::ext_by_mime($headers['Content-Type']);
        $result->location = $this->destination . $filename;

        return $result;
    }

    /**
     * Get/set returned image size.
     *
     * @param integer $size
     * @throws \Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function size($size = null)
    {
        if (func_num_args()) {
            if (!is_int($size))
            {
                $this->exception('Image size has to be integer');
            }

            // make sure passed image size is larger than 0
            if ($size < 1)
            {
                $this->exception('Image size needs to be greater than 0');
            }

            // make sure passed image size is smaller or equal 2048
            if ($size > 2048)
            {
                $this->exception('Image size needs to be smaller or equal 2048');
            }

            $this->size = $size;

            return $this;
        }

        return $this->size;
    }

    /**
     * Sets content rating.
     *
     * @param string $rating
     * @throws \Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function rating($rating = null)
    {
        if (func_num_args()) {
            // list of valid ratings
            $valid_ratings = array(self::G, self::PG, self::R, self::X);

            // force lowercase and trim leading/trailing white spaces
            $rating = trim(strtolower($rating));

            // make sure passed rating is valid
            if (!in_array($rating, $valid_ratings))
            {
                $this->exception('Invalid rating passed');
            }

            $this->rating = $rating;

            return $this;
        }

        return $this->rating;
    }

    /**
     * Sets default image if the user has no gravatar profile.
     *
     * @param string $image_default
     * @throws \Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function default_image($image_default = null)
    {
        if (func_num_args()) {
            // list of valid imagesets
            $valid_image_default_types = array(self::E404, self::MM, self::IDENTICON, self::MONSTERID, self::WAVATAR, self::RETRO, self::BLANK);

            // trim leading/trailing white spaces
            $image_default = trim($image_default);

            // is default image a url?
            $is_url = Valid::url($image_default);

            if (!$is_url)
            {
                // make sure passed imageset is valid
                if (!in_array($image_default, $valid_image_default_types))
                {
                    $this->exception('Invalid default image passed (valid: :valid_values', array(':valid_values' => implode(',', $valid_image_default_types)));
                }
            }

            $this->default_image = $image_default;

            return $this;
        }

        return $this->default_image;
    }

    /**
     * Forces gravatar to display default image.
     *
     * @param boolean $force
     * @throws \Kohana_Exception
     * @return \Gravatar
     */
    public function force_default($force = null)
    {
        if (func_num_args()) {
            if (!is_bool($force))
            {
                $this->exception('Image size has to be integer');
            }

            $this->force_default = $force;

            return $this;
        }

        return $this->force_default;
    }

    /**
     * Set the directory to save the image
     *
     * @param string $destination
     * @throws \Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function destination($destination = null)
    {
        if (func_num_args()) {
            $destination = Text::reduce_slashes($destination . DIRECTORY_SEPARATOR);

            // make sure destination is a directory
            if (!is_dir($destination))
            {
                $this->exception('Download destination is not a directory', array(), 100);
            }

            // make sure destination is writeable
            if (!is_writable($destination))
            {
                $this->exception('Download destination is not writable', array(), 105);
            }

            $this->destination = $destination;

            return $this;
        }

        return $this->destination;
    }

    /**
     * Sets whether https ot http should be used to query image.
     *
     * @param boolean $enabled
     * @throws \Kohana_Exception
     * @return \Kohana_Gravatar
     */
    public function https($enabled = null)
    {
        if (func_num_args()) {
            if (!is_bool($enabled))
            {
                $this->exception('https needs to be true or false');
            }

            $this->https = $enabled;

            return $this;
        }

        return $this->https;
    }

    /**
     * Checks whether all necessary properties have been set correctly.
     *
     * @param boolean $throw_exceptions
     * @throws \Kohana_Exception
     * @return boolean
     */
    public function validate($throw_exceptions = true)
    {
        $valid = true;

        if (!is_null($this->rating) and !$this->rating)
        {
            $valid = false;

            if ($throw_exceptions)
            {
                $this->exception('Rating has not been set');
            }
        }

        if (!is_null($this->size) and !is_int($this->size))
        {
            $valid = false;

            if ($throw_exceptions)
            {
                $this->exception('Image size has not been set');
            }
        }

        if (!is_null($this->default_image) and !$this->default_image)
        {
            $valid = false;

            if ($throw_exceptions)
            {
                $this->exception('Default image has not been set');
            }
        }

        return $valid;
    }

    /**
     * Constructor forces execution of $this->setup()
     *
     * @param array $params
     * @return \Kohana_Gravatar
     */
    protected function __construct(array $params = array())
    {
        $this->setup($params);
    }

    /**
     * Returns gravatar URL based on passed settings.
     *
     * @param $email
     * @throws \Kohana_Exception
     * @return string
     */
    protected function url_make($email)
    {
        // validate object
        $this->validate();

        // https / http
        $url = $this->https ? 'https://secure.' : 'http://www.';
        // base url
        $url .= 'gravatar.com/avatar/';
        // hashed email
        $url .= md5($email);
        // settings
        $url .= URL::query(array(
            // image size
            's' => $this->size,
            // default image
            'd' => $this->default_image,
            // image rating
            'r' => $this->rating,
            // force default imageF
            'f' => ($this->force_default ? 'y' : null)
        ), false);

        return $url;
    }

    /**
     * Kohana Exception Helper
     *
     * @param string $message
     * @param array $variables
     * @param int $code
     * @param Exception $previous
     * @throws \Kohana_Exception
     */
    protected function exception($message = '', array $variables = null, $code = 0, Exception $previous = null)
    {
        // prepend string
        $message = 'Gravatar: ' . $message;

        throw new Kohana_Exception($message, $variables, $code, $previous);
    }
}

