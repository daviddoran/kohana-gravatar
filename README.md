# Gravatar module for Kohana 3.3.x

Simple module to retrieve a user's profile image from [Gravatar](https://gravatar.com) based on a given email address.
If the email address cannot be matched with a gravatar account, gravatar will return depending on your settings a random generated image.

## Usage

Display user's gravatar

    echo Kohana_Gravatar::factory(array('email' => 'youremail@address.com'))
            ->size_set(64)
            ->https_set(false)
            ->rating_set_pg()
            ->default_set_identicon()
            ->image();

Display 64x64 gravatar

    echo Kohana_Gravatar::factory(array('email' => 'youremail@address.com'))
            ->size_set(64)
            ->https_set(false)
            ->rating_set_pg()
            ->default_set_identicon()
            ->image();

Download user's gravatar

    Kohana_Gravatar::factory(array('email' => 'youremail@address.com'))
            ->size_set(128)
            ->https_set(false)
            ->rating_set_pg()
            ->default_set_identicon()
            ->download();
