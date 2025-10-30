<?php

namespace Softadastra\Application\Meta;

class ManagerHeadPage
{
    private static $title = '';
    private static $content = "";
    private static $profile = 'My profile';
    private static $image = 'https://res.cloudinary.com/dwjbed2xb/image/upload/v1746692803/jknvd3pum7rjyuc0tj4y.jpg';

    public static function getTitle()
    {
        return self::$title;
    }

    /**
     * @param string $titre
     * @return void
     */
    public static function setTitle(string $titre)
    {
        self::$title = $titre;
    }

    public static function getProfile()
    {
        return self::$profile;
    }

    /**
     * @param string $titre
     * @return void
     */
    public static function setProfile(string $profil)
    {
        self::$profile = $profil;
    }


    /**
     * @return void
     */
    public static function getContent()
    {
        return self::$content;
    }

    /**
     * @param string $content
     * @return void
     */
    public static function setContent(string $content)
    {
        self::$content = $content;
    }

    public static function getImage()
    {
        return self::$image;
    }

    public static function setImage(string $image)
    {
        self::$image = $image;
    }
}
