# Gravatar module for Kohana 3.3.x

Simple module to retrieve a user's profile image from [Gravatar](https://gravatar.com) based on a given email address.
If the email address cannot be matched with a Gravatar account, an alternative will be returned based on the `default_image` setting.

## Usage

Display a Gravatar (using default settings)

    echo Kohana_Gravatar::factory()->image("youremail@address.com");

Display 64x64 Gravatar (only PG images and using the identicon default)

    echo Kohana_Gravatar::factory()
        ->size(64)
        ->https(false)
        ->rating(Gravatar::PG)
        ->default_image(Gravatar::IDENTICON)
        ->image("youremail@address.com");

Download Gravatar (to the current directory)

    $result = Kohana_Gravatar::factory()
        ->size(128)
        ->https(false)
        ->rating(Gravatar::R)
        ->default_image(Gravatar::IDENTICON)
        ->destination("./")
        ->download("youremail@address.com");

    echo "Gravatar saved to: ", $result->location;

Display a default Gravatar image

    echo Kohana_Gravatar::factory()
        ->default_image(Gravatar::MONSTERID)
        ->force_default(true)
        ->image("youremail@address.com");

Display multiple Gravatars with the same settings:

    $gravatar = Kohana_Gravatar::factory()
        ->size(64)
        ->https(false)
        ->rating(Gravatar::R)
        ->default_image(Gravatar::IDENTICON);

    $emails = array(
        "email1@example.com",
        "email2@example.com",
        "email3@example.com"
    );

    foreach ($emails as $email) {
        echo $gravatar->image($email);
    }

## Configuration

`$g = Kohana_Gravatar::factory();`

<dl>
    <dt>size</dt>
    <dd>
        Desired size (width and height) in pixels, e.g.
        <br /><code>$g->size(64);</code>
    </dd>

    <dt>default_image</dt>
    <dd>
        Default image (from Gravatar's predefined list or an image URL), e.g.
        <br /><code>$g->default_image(Gravatar::WAVATAR);</code>
        <br /><code>$g->default_image("http://example.com/your-default-image.png");</code>
    </dd>

    <dt>destination</dt>
    <dd>
        The directory to save downloaded Gravatar images, e.g.
        <br /><code>$g->destination("./");</code>
    </dd>

    <dt>https</dt>
    <dd>
        Enable or disable HTTPS, e.g.
        <br /><code>$g->https(true);</code>
    </dd>

    <dt>rating</dt>
    <dd>
        The acceptable image rating, e.g.
        <br /><code>$g->rating(Gravatar::PG);</code>
    </dd>

    <dt>force_default</dt>
    <dd>
        Return the default image (even if the email has a valid Gravatar), e.g.
        <br /><code>$g->force_default(true);</code>
    </dd>
</dl>

## Constants

`$g->rating(...);`

- `Gravatar::G`          suitable for display on all websites with any audience type.
- `Gravatar::PG`         may contain rude gestures, provocatively dressed individuals, the lesser swear words, or mild violence.
- `Gravatar::R`          may contain such things as harsh profanity, intense violence, nudity, or hard drug use.
- `Gravatar::X`          may contain hardcore sexual imagery or extremely disturbing violence.

`$g->default_image(...);`

- `Gravatar::E404`       return HTTP 404 error if email not found
- `Gravatar::MM`         mystery-man; a simple, cartoon-style silhouetted outline of a person
- `Gravatar::IDENTICON`  a geometric pattern based on an email hash
- `Gravatar::MONSTERID`  a generated 'monster' with different colors, faces, etc
- `Gravatar::WAVATAR`    generated faces with differing features and backgrounds
- `Gravatar::RETRO`      8-bit arcade-style pixelated faces
- `Gravatar::BLANK`      a transparent PNG image
