<?php
declare(strict_types=1);

define('ROOTPATH', dirname(__DIR__, 2));
define('ASSETPATH', __DIR__);
define('APPPATH', ROOTPATH .  '/app/multi-gudang');
define('THEMEPATH', ROOTPATH .  '/app/themes');
define('TMPPATH', ROOTPATH . '/tmp');

use Dotenv\Dotenv;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;

//use function extension_loaded;
//use function dirname;

require ROOTPATH . '/app/vendor/autoload.php';

$dotEnv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotEnv->safeLoad();

$settings = require APPPATH . '/conf/settings.php';

class AssetCtrl {
  public function __construct(private array $settings)
  {

  }

  private function getImageManager() : ImageManager
  {
    return extension_loaded('imagick') ? ImageManager::imagick() : ImageManager::gd();
  }

  public function thumb(ServerRequestInterface $request, ResponseInterface $response, array $args){
    extract($args);
    $width = (int) $width;
    $height = (int) $height;

    $path = ASSETPATH . '/' . $this->settings['upload']['directory'] . $folder;
    $ori_file = $path . $file . '.' . $ext;

    if (!is_file($ori_file))
      return $this->gambar($request, $response, $args);

    $thumb_path = ASSETPATH . '/' . $this->settings['upload']['directory'] . $folder . '/thumbs/';
    if (!is_dir($thumb_path))
      mkdir($thumb_path, 0777, true);

    $thumb_name = '';
    if (isset($crop))
      $thumb_name .= $crop;
    if (isset($width))
      $thumb_name .= $width;
    if (isset($height))
      $thumb_name .= 'x' . $height;
    $thumb_name .= '__' . $file . '.' . $ext;

    $im = $this->getImageManager();
    $image = $im->read($ori_file);

    if (isset($crop)) {
      $wp = ($width / $image->width()) * 100;
      $wh = ($height / $image->height()) * 100;
      $rz = ($wp > $wh ? ($wp / $wh) : ($wh / $wp));

      $image->scaleDown($width * $rz, $height * $rz)->crop($width, $height)->save($thumb_path . $thumb_name);
    } else {
      $image->scaleDown($width, $height)->save($thumb_path . $thumb_name);
    }

    if (is_file($thumb_path . $thumb_name)) {
    }
    $imgEnc = $image->encode();

    $length = strlen($imgEnc->__toString());

    $response = $response->withHeader('Content-Type', $imgEnc->mimetype())
      ->withHeader('Cache-Control', 'public, max-age=' . (365 * 24 * 60 * 60))
      ->withHeader('Content-Length', $length);

    $response->getBody()->write($imgEnc->__toString());

    return $response;
  }

  public function gambar(ServerRequestInterface $request, ResponseInterface $response, array $args){
    $width = (int) ($args['width'] ?? 360);
    $height = (int) ($args['height'] ?? 360);

    $ext = $args['ext'] == 'jpeg' ? 'jpg' : $args['ext'];
    $enc = 'to' . ucfirst($ext);
    $contentType = $args['ext'] == 'jpg' ? 'jpeg' : $args['ext'];

    $fsb = 2;
    $bgColor = '#adb5bd';
    $fontChar = 'f03e';

    if ($args['file'] == 'avatar') {
      $fsb = 1.5;
      $bgColor = '#f18202';
      $fontChar = 'f4fb';
    }

    $img = $this->getImageManager()->create($width, $height)->fill($bgColor);

    $fontSize = floor($width / $fsb);
    $fontSize = $fontSize < 12 ? 12 : $fontSize;

    $posX = (int) floor($width / 2);
    $posY = (int) floor($height / 2);

    $text = html_entity_decode("&#x{$fontChar};", ENT_COMPAT, 'UTF-8');
    $img->text($text, $posX, $posY, function (FontFactory $font) use ($fontSize) {
      $font->file(ASSETPATH . '/components/fontawesome/webfonts/fa-regular-400.ttf');
      $font->size($fontSize);
      $font->color('rgba(255,255,255,.5)');
      $font->align('center');
      $font->valign('middle');
    });

    $data = $img->$enc();
    $length = strlen((string) $data);

    $response = $response->withHeader('Content-Type', 'image/' . $contentType)
      ->withHeader('Cache-Control', 'public, max-age=' . (365 * 24 * 60 * 60))
      ->withHeader('Content-Length', $length);

    $response->getBody()->write((string) $data);

    return $response;
  }

  function index(ServerRequestInterface $request, ResponseInterface $response, array $args){
    $response->getBody()->write('<html><body style="text-align:center"><h1>403 Forbiden</h1></body></html>');
    return $response->withStatus(403, 'Forbidden');
  }
}

$app = AppFactory::create();
$assetCtrl = new AssetCtrl($settings);

$app->get('/uploads/{folder:.*}thumbs/{crop:c}{width:\d+}x{height:\d+}__{file:.*}.{ext:(?i)png|jpg|gif|jpeg|webp}', [$assetCtrl, 'thumb']);
$app->get('/uploads/{folder:.*}thumbs/{width:\d+}x{height:\d+}__{file:.*}.{ext:(?i)png|jpg|gif|jpeg|webp}', [$assetCtrl, 'thumb']);
$app->get('/uploads/{folder:.*}/{width:\d+}x{height:\d+}__{file:.*}.{ext:png|jpg|gif|jpeg|webp}', [$assetCtrl, 'gambar']);
$app->get('/uploads/{folder:.*}/{file:.*}.{ext:png|jpg|gif|jpeg|webp}', [$assetCtrl, 'gambar']);
$app->get('/uploads/{width:\d+}x{height:\d+}__{file:.*}.{ext:png|jpg|gif|jpeg|webp}', [$assetCtrl, 'gambar']);
$app->get('/uploads/{file:.*}.{ext:png|jpg|gif|jpeg|webp}', [$assetCtrl, 'gambar']);

$app->get('/[{u:.*}]', [$assetCtrl, 'index']);

$app->addRoutingMiddleware();

$displayErrorDetails = $settings['environtment'] != 'production';
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, false, false);

$app->run();