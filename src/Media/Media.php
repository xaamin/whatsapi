<?php
namespace Xaamin\Whatsapi\Media;

use stdClass;
use Exception;

class Media
{   
    protected $storagePath;

    protected $file;

    public function __construct($path = null)
    {
        $this->setStoragePath($path ? : getcwd() . '/media');
    }

    public function setStoragePath($path)
    {
        $this->storagePath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function compile($file, $caption = '', $type = null)
    {
        $media = $this->info($file, $type);
        $media->caption = $caption;
        $media->type = $type;
        $media->hash = null;
        
        return $media;
    }

    public function link(stdClass $media)
    {
        $link = null;

        if(isset($media->body->attributes->type))
        {
            $type = $media->body->attributes->type;

            switch ($type)
            {
                case 'audio':
                    $link = $this->linkToAudio($media->body);
                    break;
                case 'image':
                    $link = $this->linkToImage($media->body);
                    break;
                case 'location':
                    $link = $this->linkToLocation($media->body);
                    break;
                case 'video':
                    $link = $this->linkToVideo($media->body);
                    break;                
                default:
                    throw new Exception('Media type ' . $type . ' unsupported for link generation');                    
                    break;
            }
        }

        $media = new stdClass();
        $media->file = $this->file;
        $media->html= $link;

        return $media;
    }

    private function linkToAudio($media)
    {
        $link = $this->writeMediaToFile($media->attributes->file, file_get_contents($media->attributes->url), 'audios');

        return 
            '<audio controls>
                <source src="' . $link . '" type="' . $media->attributes->mimetype . '">
                ' . $this->createMediaThumb($link, $media) . '
            </audio>';
    }

    private function linkToImage($media)
    {
        $link = $this->writeMediaToFile($media->attributes->file, file_get_contents($media->attributes->url), 'pictures');

        $preview = $this->createThumb($media);

        return '<a href="' . $link . '" target="_blank"><img src="' . $preview . '"></a>';
    }    

    private function createThumb($media)
    {
        return $this->writeMediaToFile('thumb_' . substr($media->attributes->file, 0, strrpos($media->attributes->file, '.')) . '.jpg', $media->data, 'pictures');
    }

    public function createMediaThumb($link, $media)
    {
        $preview = $this->createThumb($media);

        return '<a href="' . $link . '" target="_blank"><img src="' . $preview . '"></a>';
    }

    private function linkToLocation($media)
    {
        $longitude = $media->attributes->longitude;

        $latitude = $media->attributes->latitude;

        $link = $this->writeMediaToFile(preg_replace('/\D+/', '', $longitude . $latitude) . '.jpg', $media->data, 'pictures');

        return '<a href="http://www.google.com/maps?q=' . $latitude . ',' . $longitude . '" target="_blank"><img src="' . $link . '"></a>';
    }

    private function linkToVideo($media)
    {
        $link = $this->writeMediaToFile($media->attributes->file, file_get_contents($media->attributes->url), 'videos');

        return 
            '<video width="220" height="140" controls>
                <source src="' . $link . '" type="' . $media->attributes->mimetype . '">
                ' . $this->createMediaThumb($link, $media) . '
            </video>';
    }

    private function writeMediaToFile($file, $data, $path)
    {
        $file = $this->storagePath . $path . DIRECTORY_SEPARATOR . $file;

        if(!isset($this->file))
        {
            $this->file = $file;
        }

        if(!file_exists($file))
        {
            @file_put_contents($file, $data, LOCK_EX);
        }

        return str_replace(getcwd() . DIRECTORY_SEPARATOR , '', $file);
    }

    public function info($file, $type = null)
    {
        if(!is_dir($this->storagePath))
        {
            throw new Exception('Tmp path for media not setting up. Provide one.');
        }

        $media = new stdClass();

        $size = $this->getMaxMediaSizeAllowed($type);

        if (filter_var($file, FILTER_VALIDATE_URL) !== false) 
        {
            $media->url = $file;

            //File is a URL. Create a curl connection but DON'T download the body content
            //because we want to see if file is too big.
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $file);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.11) Gecko/20071127 Firefox/2.0.0.11');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_NOBODY, true);

            if (curl_exec($curl) === false) 
            {
                throw new Exception('File ' . $file . ' can\'t be downloaded.');
            }

            //While we're here, get mime type and filesize and extension
            $info = curl_getinfo($curl);
            $media->filesize = $info['download_content_length'];
            $media->filemimetype = $info['content_type'];
            $media->fileextension = pathinfo(parse_url($media->url, PHP_URL_PATH), PATHINFO_EXTENSION);

            //Only download file if it's not too big
            //TODO check what max file size whatsapp server accepts.
            if ($media->filesize < $size) 
            {
                //Create temp file in media folder. Media folder must be writable!
                $media->file = $this->storagePath . 'tmp/' . 'WHA-' . str_replace('.', '', microtime(true)) .'-' . substr( md5(rand()), 0, 6) . '.' . $media->fileextension;

                $fp = fopen($media->file, 'w');

                if ($fp) 
                {
                    curl_setopt($curl, CURLOPT_NOBODY, false);
                    curl_setopt($curl, CURLOPT_BUFFERSIZE, 1024);
                    curl_setopt($curl, CURLOPT_FILE, $fp);
                    curl_exec($curl);
                    fclose($fp);
                } 

                //Success
                curl_close($curl);
                return $media;
            } 
            else 
            {
                //File too big. Don't Download.
                curl_close($curl);
                throw new Exception('File ' . $file .  ' too big. Don\'t Download.');
            }
        } 
        else if (file_exists($file)) 
        {
            //Local file

            $media->filesize = filesize($file);

            if ($media->filesize < $size) 
            {
                $media->file = $file;
                $media->fileextension = pathinfo($file, PATHINFO_EXTENSION);
                $media->filemimetype = get_mime($file);
                return $media;
            } 
            else 
            {
                //File too big
                throw new Exception('Local File ' . $file . ' too big');
            }
        }

        //Couldn't tell what file was, local or URL.
        throw new Exception('Couldn\'t tell what file was, local or URL');
    }

    private function getMaxMediaSizeAllowed($type)
    {
        $size = null;

        switch ($type)
        {
            case 'image':
                $size = 5 * 1024 * 1024; // Easy way to set maximum file size for image media type.
                break;
            case 'audio':
                $size = 10 * 1024 * 1024; // Easy way to set maximum file size for audio media type.
                break;
            case 'video':
                $size = 20 * 1024 * 1024; // Easy way to set maximum file size for video media type.
                break;            
            default:
                throw new Exception('Media type ' . $type . ' unsupported');
                break;
        }

        return $size;            
    }
}