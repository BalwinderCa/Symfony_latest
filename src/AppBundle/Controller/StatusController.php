<?php

namespace App\AppBundle\Controller;

use App\AppBundle\Entity\Transaction;
use App\AppBundle\Entity\Status;
use App\AppBundle\Forms\ImageType;
use App\AppBundle\Forms\GifType;
use App\AppBundle\Forms\QuoteType;
use App\AppBundle\Forms\StatusReviewType;
use App\AppBundle\Forms\QuoteReviewType;
use App\AppBundle\Forms\VideoType;
use App\AppBundle\Forms\VideoTypeUrl;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\MediaBundle\Entity\Media;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Knp\Component\Pager\PaginatorInterface; // Correct import
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Asset\Packages as AssetHelper;


use Doctrine\ORM\EntityManagerInterface;

class StatusController extends AbstractController 
{

	private $entityManager;
    private CacheManager $imagineCacheManager;
	private  $params;
	private $token;
	private $assetHelper;

    // Inject the EntityManagerInterface into the controller
    public function __construct(EntityManagerInterface $entityManager,AssetHelper $assetHelper,CacheManager $imagineCacheManager,ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
        $this->imagineCacheManager = $imagineCacheManager;
		$this->params = $params;
		$this->assetHelper = $assetHelper;
		$this->token = "4F5A9C3D9A86FA54EACEDDD635185";
    }

    function remove_emoji ($string="") 
	{
		$string = str_replace(" ","736489290",$string);
		
		// PREG_REPLACE REMOVE ALL OTHER CHARACTERS THAT NOT AVAIALABLE IN PREG_REPLACE FIRST
		// PARAMETER YOU CANNOT UNDERSTAND FIRST PARAMETER YOU MUST READ PHP REGULAR EXPRESSION!
		$string = preg_replace('/[^A-Za-z0-9]/','',$string);
		
		//STRIP_TAGS REMOVE HTML TAGS
		$string=strip_tags($string,"");
		//HERE WE REMOVE WHITE SPACES AND RETURN IT
		
		$newString =  trim($string);
		$newString = str_replace("736489290"," ",$newString);

		return $newString;
    }

	public function addVideo(Request $request) 
	{
		$video = new Status();
		$video->setType("video");
		$form = $this->createForm(VideoType::class, $video);
		$em = $this->entityManager;

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			if ($video->getFile() != null and $video->getFilevideo() != null) {
				$media = new Media();
				$media->setFile($video->getFile());
				$media->setEnabled(true);
				$media->upload($this->params->get('kernel.project_dir') . '/public/uploads');

				$video->setMedia($media);

				$video_media = new Media();
				$video_media->setFile($video->getFilevideo());
				$video_media->setEnabled(true);
				$video_media->upload($this->params->get('kernel.project_dir') . '/public/uploads');

				$video->setVideo($video_media);

				$video->setUser($this->getUser());
				$video->setReview(false);
				$video->setDownloads(0);
				$em->persist($media);
				$em->flush();

				$em->persist($video_media);
				$em->flush();

				$em->persist($video);
				$em->flush();
				$this->addFlash('success', 'Operation has been done successfully');
				return $this->redirect($this->generateUrl('app_status_index'));
			} else {
				$photo_error = new FormError("Required image file");
				$video_error = new FormError("Required video file");
				$form->get('file')->addError($photo_error);
				$form->get('filevideo')->addError($video_error);
			}
		}
		return $this->render("@AppBundle/Status/video_add.html.twig", array("form" => $form->createView()));
	}


    public function editVideo(Request $request, $id)
	{
        $em = $this->entityManager;
        $video = $em->getRepository(Status::class)->findOneBy(array("id" => $id, "review" => false));
        if ($video == null) {
            throw new NotFoundHttpException("Page not found");
        }
        $form = $this->createForm(VideoType::class, $video);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($video->getFile() != null) {
                $media = new Media();
                $media_old = $video->getMedia();
                $media->setFile($video->getFile());
                $media->setEnabled(true);
                $media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->persist($media);
                $em->flush();
                $video->setMedia($media);
                $em->flush();
                $media_old->delete($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->remove($media_old);
                $em->flush();
            }

            if ($video->getFilevideo() != null) {
                $video_media = new Media();
                $video_media_old = $video->getVideo();
                $video_media->setFile($video->getFilevideo());
                $video_media->setEnabled(true);
                $video_media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->persist($video_media);
                $em->flush();

                $video->setVideo($video_media);
                $em->flush();

                $video_media_old->delete($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->remove($video_media_old);
                $em->flush();
            }

            $em->persist($video);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($this->generateUrl('app_status_index'));
        }
        return $this->render("@AppBundle/Status/video_edit.html.twig", array("form" => $form->createView()));
    }

public function addVideoUrl(Request $request) {
        $video = new Status();
        $video->setType("video");
        $form = $this->createForm(VideoTypeUrl::class, $video);
        $em = $this->entityManager;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file_ext = substr(strrchr($video->getUrlvideo(), '.'), 1);
            switch ($file_ext) {
            case 'mp4':
                $file_type = "video/mp4";
                break;
            case 'webm':
                $file_type = "video/webm";
                break;
            default:
                $file_type = "none";
                break;
            }

            if ($file_type != "none") {
                if ($video->getFile() != null) {
                    $media = new Media();
                    $media->setFile($video->getFile());
                    $media->setEnabled(true);
                    $media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                    $video->setMedia($media);

                    $video_media = new Media();
                    $video_media->setTitre($video->getTitle());
                    $video_media->setUrl($video->getUrlvideo());
                    $video_media->setExtension($file_ext);
                    $video_media->setType($file_type);
                    $video_media->setEnabled(false);

                    $video->setVideo($video_media);

                    $video->setUser($this->getUser());
                    $video->setReview(false);
                    $video->setDownloads(0);
                    $em->persist($media);
                    $em->flush();

                    $em->persist($video_media);
                    $em->flush();

                    $em->persist($video);
                    $em->flush();
                    $this->addFlash('success', 'Operation has been done successfully');
                    return $this->redirect($this->generateUrl('app_status_index'));
                } else {
                    $photo_error = new FormError("Required image file");
                    $form->get('file')->addError($photo_error);
                }
            } else {
                $type_error = new FormError("Url has video not supported");
                $form->get('urlvideo')->addError($type_error);
            }
        }
        return $this->render("@AppBundle/Status/video_add_url.html.twig", array("form" => $form->createView()));
    }
    public function editVideoUrl(Request $request, $id) {
        $em = $this->entityManager;
        $video = $em->getRepository(Status::class)->findOneBy(array("id" => $id, "review" => false));
        if ($video == null) {
            throw new NotFoundHttpException("Page not found");
        }
        $videourl = $video->getVideo()->getUrl();
        $video->setUrlvideo($videourl);
        $form = $this->createForm(VideoTypeUrl::class, $video);
        $form->handleRequest($request);

        $file_ext = substr(strrchr($video->getUrlvideo(), '.'), 1);
        switch ($file_ext) {
        case 'mp4':
            $file_type = "video/mp4";
            break;
        case 'webm':
            $file_type = "video/webm";
            break;
        default:
            $file_type = "none";
            break;
        }

        if ($file_type != "none") {

            if ($form->isSubmitted() && $form->isValid()) {
                if ($videourl != $video->getUrlvideo()) {

                    $video_media = new Media();
                    $video_media->setTitre($video->getTitle());
                    $video_media->setUrl($video->getUrlvideo());
                    $video_media->setExtension($file_ext);
                    $video_media->setType($file_type);
                    $video_media->setEnabled(false);

                    $video_media_old = $video->getVideo();
                    $em->persist($video_media);
                    $em->flush();

                    $video->setVideo($video_media);
                    $em->flush();

                    $video_media_old->delete($this->params->get('kernel.project_dir') . '/public/uploads');
                    $em->remove($video_media_old);
                    $em->flush();
                }
                if ($video->getFile() != null) {
                    $media = new Media();
                    $media_old = $video->getMedia();
                    $media->setFile($video->getFile());
                    $media->setEnabled(true);
                    $media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                    $em->persist($media);
                    $em->flush();
                    $video->setMedia($media);
                    $em->flush();
                    $media_old->delete($this->params->get('kernel.project_dir') . '/public/uploads');
                    $em->remove($media_old);
                    $em->flush();
                }

                $em->persist($video);
                $em->flush();
                $this->addFlash('success', 'Operation has been done successfully');
                return $this->redirect($this->generateUrl('app_status_index'));
            }
        } else {
            $type_error = new FormError("Url has video not supported");
            $form->get('urlvideo')->addError($type_error);
        }
        return $this->render("@AppBundle/Status/video_edit_url.html.twig", array("form" => $form->createView()));
    }


    public function addQuote(Request $request) {
        $status = new Status();
        $status->setType("quote");
        $form = $this->createForm(QuoteType::class, $status);
        $em = $this->entityManager;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
                $status->setDescription($this->remove_emoji($status->getTitle()));
                $status->setTitle(base64_encode($status->getTitle()));

                $status->setUser($this->getUser());
                $status->setReview(false);
                $status->setDownloads(0);
                $em->persist($status);
                $em->flush();
                $this->addFlash('success', 'Operation has been done successfully');
                return $this->redirect($this->generateUrl('app_status_index'));
        
        }
        return $this->render("@AppBundle/Status/quote_add.html.twig", array("form" => $form->createView()));
    }
    public function editQuote(Request $request,$id)
    {
        $em=$this->entityManager;
        $status=$em->getRepository(Status::class)->findOneBy(array("id"=>$id,"review"=>false));
        if ($status==null) {
            throw new NotFoundHttpException("Page not found");
        }
        $status->setTitle(base64_decode($status->getTitle()));

        $form = $this->createForm(QuoteType::class,$status);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $status->setDescription($this->remove_emoji($status->getTitle()));
            $status->setTitle(base64_encode($status->getTitle()));
            $em->persist($status);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($this->generateUrl('app_status_index'));
        }
        return $this->render("@AppBundle/Status/quote_edit.html.twig",array("form"=>$form->createView()));
    }
	public function addImage(Request $request) {
		$video = new Status();
		$video->setType("image");
		$form = $this->createForm(ImageType::class, $video);
		$em = $this->entityManager;

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			if ($video->getFile() != null) {
				$media = new Media();
				$media->setFile($video->getFile());
				$media->setEnabled(true);
				$media->upload($this->params->get('kernel.project_dir') . '/public/uploads');

				$video->setMedia($media);

				$video->setUser($this->getUser());
				$video->setReview(false);
				$video->setDownloads(0);
				$em->persist($media);
				$em->flush();

				$em->persist($video);
				$em->flush();
				$this->addFlash('success', 'Operation has been done successfully');
				return $this->redirect($this->generateUrl('app_status_index'));
			} else {
				$photo_error = new FormError("Required image file");
				$form->get('file')->addError($photo_error);
			}
		}
		return $this->render("@AppBundle/Status/image_add.html.twig", array("form" => $form->createView()));
	}
    public function editImage(Request $request, $id) {
        $em = $this->entityManager;
        $video = $em->getRepository(Status::class)->findOneBy(array("id" => $id, "review" => false));
        if ($video == null) {
            throw new NotFoundHttpException("Page not found");
        }
        $form = $this->createForm(ImageType::class, $video);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($video->getFile() != null) {
                $media = new Media();
                $media_old = $video->getMedia();
                $media->setFile($video->getFile());
                $media->setEnabled(true);
                $media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->persist($media);
                $em->flush();
                $video->setMedia($media);
                $em->flush();
                $media_old->delete($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->remove($media_old);
                $em->flush();
            }
            $em->persist($video);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($this->generateUrl('app_status_index'));
        }
        return $this->render("@AppBundle/Status/image_edit.html.twig", array("form" => $form->createView()));
    }

    public function addGif(Request $request) {
        $video = new Status();
        $video->setType("gif");
        $form = $this->createForm(GifType::class, $video);
        $em = $this->entityManager;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($video->getFilegif() != null) {
                $media = new Media();
                $media->setFile($video->getFilegif());
                $media->setEnabled(true);
                $media->upload($this->params->get('kernel.project_dir') . '/public/uploads');

                $video->setMedia($media);

                $video->setUser($this->getUser());
                $video->setReview(false);
                $video->setDownloads(0);
                $em->persist($media);
                $em->flush();

                $em->persist($video);
                $em->flush();
                $this->addFlash('success', 'Operation has been done successfully');
                return $this->redirect($this->generateUrl('app_status_index'));
            } else {
                $photo_error = new FormError("Required image file");
                $form->get('filegif')->addError($photo_error);
            }
        }
        return $this->render("@AppBundle/Status/gif_add.html.twig", array("form" => $form->createView()));
    }
    public function editGif(Request $request, $id) {
        $em = $this->entityManager;
        $video = $em->getRepository(Status::class)->findOneBy(array("id" => $id, "review" => false));
        if ($video == null) {
            throw new NotFoundHttpException("Page not found");
        }
        $form = $this->createForm(GifType::class, $video);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($video->getFilegif() != null) {
                $media = new Media();
                $media_old = $video->getMedia();
                $media->setFile($video->getFilegif());
                $media->setEnabled(true);
                $media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->persist($media);
                $em->flush();
                $video->setMedia($media);
                $em->flush();
                $media_old->delete($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->remove($media_old);
                $em->flush();
            }
            $em->persist($video);
            $em->flush();
            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($this->generateUrl('app_status_index'));
        }
        return $this->render("@AppBundle/Status/gif_edit.html.twig", array("form" => $form->createView()));
    }

	
	public function api_add_angry(Request $request, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$id = $request->get("id");
		$em = $this->entityManager;
		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$video->setAngry($video->getAngry() + 1);
		$em->flush();
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($video->getAngry(), 'json');
		return new Response($jsonContent);
	}

	public function api_add_share(Request $request, $token) {
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }
        $em = $this->entityManager;
        $id = $request->get("id");
        $userId = $request->get("user");
        $userKey = $request->get("key");
        $status = $em->getRepository(Status::class)->findOneBy(array("id"=>$id,"enabled"=>true));
        if ($status == null) {
            throw new NotFoundHttpException("Page not found");
        }
        if($userId){
            $user = $em->getRepository(User::class)->find($userId);
            if ($user) {
                if (sha1($user->getPassword()) == $userKey) {
                    $transaction = $em->getRepository("AppBundle:Transaction")->findOneBy(array("user"=>$user,"status"=>$status,"type"=>"share_".$status->getType()));
                    if ($transaction==null) {
                        $transaction = new Transaction();
                        $setting = $em->getRepository(Settings::class)->findOneBy(array());
                        $transaction->setPoints($setting->getPoints("share".$status->getType()));
                        $transaction->setStatus($status);
                        $transaction->setUser($user);
                        $transaction->setType("share_".$status->getType());
                        $em->persist($transaction);
                        $em->flush();
                    }
                }
            }
        }        

        $status->setDownloads($status->getDownloads() + 1);
        $em->flush();
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($status->getDownloads(), 'json');
        return new Response($jsonContent);
	}
    public function api_add_view(Request $request, $token) {
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }
        $em = $this->entityManager;
        $id = $request->get("id");
        $userId = $request->get("user");
        $userKey = $request->get("key");
        $status = $em->getRepository(Status::class)->findOneBy(array("id"=>$id,"enabled"=>true));
        if ($status == null) {
            throw new NotFoundHttpException("Page not found");
        }
        if($userId){
            $user = $em->getRepository(User::class)->find($userId);
            if ($user) {
                if (sha1($user->getPassword()) == $userKey) {
                    $transaction = $em->getRepository("AppBundle:Transaction")->findOneBy(array("user"=>$user,"status"=>$status,"type"=>"view_".$status->getType()));
                    if ($transaction==null) {
                        $transaction = new Transaction();
                        $setting = $em->getRepository(Settings::class)->findOneBy(array());
                        $transaction->setPoints($setting->getPoints("view".$status->getType()));
                        $transaction->setStatus($status);
                        $transaction->setUser($user);
                        $transaction->setType("view_".$status->getType());
                        $em->persist($transaction);
                        $em->flush();
                    }
                }
            }
        }        

        $status->setViews($status->getViews() + 1);
        $em->flush();
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($status->getViews(), 'json');
        return new Response($jsonContent);
    }

	public function api_add_haha(Request $request, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$id = $request->get("id");
		$em = $this->entityManager;
		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$video->setHaha($video->getHaha() + 1);
		$em->flush();
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($video->getHaha(), 'json');
		return new Response($jsonContent);
	}

	public function api_add_like(Request $request, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$id = $request->get("id");
		$em = $this->entityManager;
		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$video->setLike($video->getLike() + 1);
		$em->flush();
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($video->getLike(), 'json');
		return new Response($jsonContent);
	}

	public function api_add_love(Request $request, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$id = $request->get("id");
		$em = $this->entityManager;
		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$video->setLove($video->getLove() + 1);
		$em->flush();
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($video->getLove(), 'json');
		return new Response($jsonContent);
	}

	public function api_add_sad(Request $request, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$id = $request->get("id");
		$em = $this->entityManager;
		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$video->setSad($video->getSad() + 1);
		$em->flush();
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($video->getSad(), 'json');
		return new Response($jsonContent);
	}

	public function api_add_woow(Request $request, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$id = $request->get("id");
		$em = $this->entityManager;
		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$video->setWoow($video->getWoow() + 1);
		$em->flush();
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($video->getWoow(), 'json');
		return new Response($jsonContent);
	}

	// Helper function to calculate time difference
	private function getTimeDifference($createdDateTime)
	{
		$currentDateTime = new \DateTime();
		$interval = $currentDateTime->diff($createdDateTime);
		return $interval->format('%y years, %m months, %d days, %h hours, %i minutes ago');
	}

	// Helper function to prepare response data for a status
	private function prepareStatusResponseData(Status $status, Request $request)
	{
		$a = [];

		// Basic fields
		$a["id"] = $status->getId();
		$a["kind"] = $status->getType();
		$a["title"] = $status->getTitle();
		$a["description"] = $status->getDescription();
		$a["review"] = $status->getReview();
		$a["comment"] = $status->getComment();
		$a["comments"] = sizeof($status->getComments());
		$a["downloads"] = $status->getDownloads();
		$a["font"] = $status->getFont();
		$a["views"] = $status->getViews();
		$a["user"] = $status->getUser()->getName();
		$a["userid"] = $status->getUser()->getId();
		$a["userimage"] = $status->getUser()->getImage();

		// Handling video/media data
		if ($status->getType() != "quote") {
			if ($status->getVideo()) {
				$a["type"] = $status->getVideo()->getType();
				$a["extension"] = $status->getVideo()->getExtension();
			} else {
				$a["type"] = $status->getMedia()->getType();
				$a["extension"] = $status->getMedia()->getExtension();
			}

			$a["thumbnail"] = $this->imagineCacheManager->getBrowserPath($this->assetHelper->getUrl($status->getMedia()->getLink()), 'status_thumb_api');

			if ($status->getVideo()) {
				$a["original"] = $status->getVideo()->getEnabled() ?
					$request->getSchemeAndHttpHost() . "/" . $status->getVideo()->getLink() :
					$status->getVideo()->getLink();
			} else {
				$a["original"] = $request->getSchemeAndHttpHost() . "/" . $status->getMedia()->getLink();
			}
		} else {
			$a["color"] = $status->getColor();
		}

		// Add additional data for the time difference
		$a["created"] = $this->getTimeDifference($status->getCreated());

		// Additional reactions and tags
		$a["tags"] = $status->getTags();
		$a["like"] = $status->getLike();
		$a["love"] = $status->getLove();
		$a["woow"] = $status->getWoow();
		$a["angry"] = $status->getAngry();
		$a["sad"] = $status->getSad();
		$a["haha"] = $status->getHaha();

		return $a;
	}


	public function api_all(Request $request, $page, $order, $language, $token)
	{
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}

		$nombre = 30;
		$em = $this->entityManager;
		$repository = $em->getRepository(Status::class);

		// Build query based on language filter
		if ($language == 0) {
			$query = $repository->createQueryBuilder('w')
				->where("w.enabled = true")
				->addOrderBy('w.' . $order, 'DESC')
				->addOrderBy('w.id', 'asc')
				->setFirstResult($nombre * $page)
				->setMaxResults($nombre)
				->getQuery();
		} else {
			$query = $repository->createQueryBuilder('w')
				->leftJoin('w.languages', 'l')
				->where('l.id in (' . $language . ')', "w.enabled = true")
				->addOrderBy('w.' . $order, 'DESC')
				->addOrderBy('w.id', 'asc')
				->setFirstResult($nombre * $page)
				->setMaxResults($nombre)
				->getQuery();
		}

		$videos = $query->getResult();

		// Prepare the response data using the helper function
		$list = [];
		foreach ($videos as $status) {
			$list[] = $this->prepareStatusResponseData($status, $request);
		}

		// Return JSON response
		return new JsonResponse($list, JSON_UNESCAPED_UNICODE);
	}


	public function api_by_me(Request $request, $page, $user, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$nombre = 30;
		$em = $this->entityManager;
		//$imagineCacheManager = $this->get('liip_imagine.cache.manager');
		$repository = $em->getRepository(Status::class);
		$query = $repository->createQueryBuilder('w')
			->where('w.user = :user')
			->setParameter('user', $user)
			->addOrderBy('w.created', 'DESC')
			->addOrderBy('w.id', 'asc')
			->setFirstResult($nombre * $page)
			->setMaxResults($nombre)
			->getQuery();
		$videos = $query->getResult();

		// Prepare the response data using the helper function
		$list = [];
		foreach ($videos as $status) {
			$list[] = $this->prepareStatusResponseData($status, $request);
		}

		// Return JSON response
		return new JsonResponse($list, JSON_UNESCAPED_UNICODE);
		//return $this->render('@AppBundle/Status/api_all.html.php', array("videos" => $videos));
	}

	public function api_by_query(Request $request, $order, $language, $page, $query, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$nombre = 30;
		$em = $this->entityManager;
		//$imagineCacheManager = $this->get('liip_imagine.cache.manager');
		$repository = $em->getRepository(Status::class);
		if ($language == 0) {
			$query_dql = $repository->createQueryBuilder('w')
				->where("w.enabled = true", "LOWER(w.title) like LOWER('%" . $query . "%') OR LOWER(w.tags) like LOWER('%" . $query . "%')  OR LOWER(w.description) like LOWER('%" . $query . "%') ")
				->addOrderBy('w.' . $order, 'DESC')
				->addOrderBy('w.id', 'asc')
				->setFirstResult($nombre * $page)
				->setMaxResults($nombre)
				->getQuery();
		} else {
			$language = str_replace("_", ",", $language);
			$query_dql = $repository->createQueryBuilder('w')
				->leftJoin('w.languages', 'l')
				->where('l.id in (' . $language . ')', "LOWER(w.title) like LOWER('%" . $query . "%') OR LOWER(w.tags) like LOWER('%" . $query . "%') ")
				->addOrderBy('w.' . $order, 'DESC')
				->addOrderBy('w.id', 'asc')
				->setFirstResult($nombre * $page)
				->setMaxResults($nombre)
				->getQuery();
		}
		$videos = $query_dql->getResult();

		// Prepare the response data using the helper function
		$list = [];
		foreach ($videos as $status) {
			$list[] = $this->prepareStatusResponseData($status, $request);
		}

		// Return JSON response
		return new JsonResponse($list, JSON_UNESCAPED_UNICODE);

		//return $this->render('@AppBundle/Status/api_all.html.php', array("videos" => $videos));
	}

	public function api_by_random(Request $request, $language, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}

		$nombre = 6;
		$em = $this->entityManager;
		//$imagineCacheManager = $this->get('liip_imagine.cache.manager');
		$repository = $em->getRepository(Status::class);

		if ($language == 0) {
			$max = sizeof($repository->createQueryBuilder('g')
					->where("g.enabled = true")
					->getQuery()->getResult());

			$query = $repository->createQueryBuilder('g')
				->where("g.enabled = true")
				->orderBy('g.created', 'DESC')
				->setFirstResult(rand(0, $max-5))
				->setMaxResults($nombre)
				->orderBy('g.downloads', 'DESC')
				->getQuery();
		} else {
			$max = sizeof($repository->createQueryBuilder('g')
					->leftJoin('g.languages', 'l')
					->where('l.id in (' . $language . ')', "g.enabled = true")

					->getQuery()->getResult());

			$query = $repository->createQueryBuilder('g')
				->leftJoin('g.languages', 'l')
				->where('l.id in (' . $language . ')', "g.enabled = true")

                ->setFirstResult(rand(0, $max-5))
				->orderBy('g.downloads', 'DESC')
				->setMaxResults($nombre)
				->getQuery();
		}

		$videos = $query->getResult();

		// Prepare the response data using the helper function
		$list = [];
		foreach ($videos as $status) {
			$list[] = $this->prepareStatusResponseData($status, $request);
		}

		// Return JSON response
		return new JsonResponse($list, JSON_UNESCAPED_UNICODE);
		//return $this->render('@AppBundle/Status/api_all.html.php', array("videos" => $videos));
	}

	public function api_by_user(Request $request, $page, $order, $language, $user, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$nombre = 30;
		$em = $this->entityManager;
		//$imagineCacheManager = $this->get('liip_imagine.cache.manager');
		$repository = $em->getRepository(Status::class);
		if ($language == 0) {
			$query = $repository->createQueryBuilder('w')
				->where('w.user = :user', "w.enabled = true")
				->setParameter('user', $user)
				->addOrderBy('w.' . $order, 'DESC')
				->addOrderBy('w.id', 'asc')
				->setFirstResult($nombre * $page)
				->setMaxResults($nombre)
				->getQuery();
		} else {
			$query = $repository->createQueryBuilder('w')
				->leftJoin('w.languages', 'l')
				->where('l.id in (' . $language . ')', "w.enabled = true", 'w.user = :user')

				->setParameter('user', $user)
				->addOrderBy('w.' . $order, 'DESC')
				->addOrderBy('w.id', 'asc')
				->setFirstResult($nombre * $page)
				->setMaxResults($nombre)
				->getQuery();
		}
		$videos = $query->getResult();
		// Prepare the response data using the helper function
		$list = [];
		foreach ($videos as $status) {
			$list[] = $this->prepareStatusResponseData($status, $request);
		}

		// Return JSON response
		return new JsonResponse($list, JSON_UNESCAPED_UNICODE);
		//return $this->render('@AppBundle/Status/api_all.html.php', array("videos" => $videos));
	}

	public function api_delete_angry(Request $request, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$id = $request->get("id");
		$em = $this->entityManager;
		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$video->setAngry($video->getAngry() - 1);
		$em->flush();
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($video->getAngry(), 'json');
		return new Response($jsonContent);
	}

	public function api_delete_haha(Request $request, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$id = $request->get("id");
		$em = $this->entityManager;
		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$video->setHaha($video->getHaha() - 1);
		$em->flush();
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($video->getHaha(), 'json');
		return new Response($jsonContent);
	}

	public function api_delete_like(Request $request, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$id = $request->get("id");
		$em = $this->entityManager;
		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$video->setLike($video->getLike() - 1);
		$em->flush();
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($video->getLike(), 'json');
		return new Response($jsonContent);
	}

	public function api_delete_love(Request $request, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$id = $request->get("id");
		$em = $this->entityManager;
		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$video->setLove($video->getLove() - 1);
		$em->flush();
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($video->getLove(), 'json');
		return new Response($jsonContent);
	}

	public function api_delete_sad(Request $request, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$id = $request->get("id");
		$em = $this->entityManager;
		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$video->setSad($video->getSad() - 1);
		$em->flush();
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($video->getSad(), 'json');
		return new Response($jsonContent);
	}

	public function api_delete_woow(Request $request, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$id = $request->get("id");
		$em = $this->entityManager;
		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$video->setWoow($video->getWoow() - 1);
		$em->flush();
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($video->getWoow(), 'json');
		return new Response($jsonContent);
	}

	public function api_my(Request $request, $page, $user, $token) {
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$nombre = 30;
		$em = $this->entityManager;
		//$imagineCacheManager = $this->get('liip_imagine.cache.manager');
		$repository = $em->getRepository(Status::class);
		$query = $repository->createQueryBuilder('w')
			->leftJoin('w.user', 'c')
			->where('c.id = :user')
			->setParameter('user', $user)
			->addOrderBy('w.created', 'DESC')
			->addOrderBy('w.id', 'asc')
			->setFirstResult($nombre * $page)
			->setMaxResults($nombre)
			->getQuery();

		$videos = $query->getResult();
		// Prepare the response data using the helper function
		$list = [];
		foreach ($videos as $status) {
			$list[] = $this->prepareStatusResponseData($status, $request);
		}

		// Return JSON response
		return new JsonResponse($list, JSON_UNESCAPED_UNICODE);
		//return $this->render('@AppBundle/Status/api_all.html.php', array("videos" => $videos));
	}

    public function api_uploadQuote(Request $request, $token) {

        $id = $request->get("id");
        $key = $request->get("key");
        $quote = $request->get("quote");
        $color = $request->get("color");
        $font = $request->get("font");

        $language_ids = $request->get("language");
        $language_list = explode("_", $language_ids);

        $categories_ids = $request->get("categories");
        $categories_list = explode("_", $categories_ids);

        $code = "200";
        $message = "Ok";
        $values = array();
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->findOneBy(array("id" => $id));
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        if (sha1($user->getPassword()) != $key) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user) {

                $w = new Status();
                $w->setType("quote");
                $w->setColor($color);
                $w->setFont($font);
                $w->setDownloads(0);
                if (!$user->getTrusted()) {
                    $w->setEnabled(false);
                    $w->setReview(true); 
                }else{
                    $w->setEnabled(true);
                    $w->setReview(false);         
                }

                $w->setComment(true);
                $w->setDescription($this->remove_emoji(base64_decode($quote)));
                $w->setTitle($quote);

                $w->setUser($user);

                foreach ($language_list as $key => $id_language) {
                    $language_obj = $em->getRepository(Language::class)->find($id_language);
                    if ($language_obj) {
                        $w->addlanguage($language_obj);
                    }
                }
                foreach ($categories_list as $key => $id_category) {
                    $category_obj = $em->getRepository(Category::class)->find($id_category);
                    if ($category_obj) {
                        $w->addCategory($category_obj);
                    }
                }

                $em->persist($w);
                $em->flush();

                if ($user->getTrusted()) {
                    $transaction = new Transaction();
                    $setting = $em->getRepository(Settings::class)->findOneBy(array());
                    $transaction->setPoints($setting->getPoints("add".$w->getType()));
                    $transaction->setStatus($w);
                    $transaction->setUser($user);
                    $transaction->setType("add_".$w->getType());
                    $em->persist($transaction);
                    $em->flush();
                    $this->sendNotif($em,$w);
                }

            
        }
        $error = array(
            "code" => $code,
            "message" => $message,
            "values" => $values,
        );
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, 'json');
        return new Response($jsonContent);
    }
    public function sendNotif(EntityManagerInterface $em, Status $selectedStatus)
	{
		$user = $selectedStatus->getUser();
		if (!$user) {
			throw $this->createNotFoundException('User not found.');
		}

		$tokens = [$user->getToken()];
		$original = '';
		$thumbnail = '';
		$type = '';
		$extension = '';
		$color = '';

		if ($selectedStatus->getType() !== 'quote') {
			// Determine type and extension
			if ($video = $selectedStatus->getVideo()) {
				$type = $video->getType();
				$extension = $video->getExtension();
			} elseif ($media = $selectedStatus->getMedia()) {
				$type = $media->getType();
				$extension = $media->getExtension();
			}

			// Generate thumbnail and original media URLs
			if ($media) {
				$thumbnail = $this->imagineCacheManager->getBrowserPath($media->getLink(), 'status_thumb_api');
				$original = $this->generateAbsoluteUrl($media->getLink());
			}

			if ($video && $video->getEnabled()) {
				$original = $this->generateAbsoluteUrl($video->getLink());
			}
		} else {
			// Handle color for "quote" type
			$color = $selectedStatus->getColor();
		}

		// Build notification message
		$message = [
			'type' => 'status',
			'kind' => $selectedStatus->getType(),
			'id' => $selectedStatus->getId(),
			'status_title' => $selectedStatus->getTitle(),
			'status_description' => $selectedStatus->getDescription(),
			'status_review' => $selectedStatus->getReview(),
			'status_comment' => $selectedStatus->getComment(),
			'status_comments' => count($selectedStatus->getComments()),
			'status_downloads' => $selectedStatus->getDownloads(),
			'status_views' => $selectedStatus->getViews(),
			'status_font' => $selectedStatus->getFont(),
			'status_user' => $user->getName(),
			'status_userid' => $user->getId(),
			'status_userimage' => $user->getImage(),
			'status_type' => $type,
			'status_extension' => $extension,
			'status_thumbnail' => $thumbnail,
			'status_original' => $original,
			'status_color' => $color,
			'status_created' => 'Now',
			'status_tags' => $selectedStatus->getTags(),
			'status_like' => $selectedStatus->getLike(),
			'status_love' => $selectedStatus->getLove(),
			'status_woow' => $selectedStatus->getWoow(),
			'status_angry' => $selectedStatus->getAngry(),
			'status_sad' => $selectedStatus->getSad(),
			'status_haha' => $selectedStatus->getHaha(),
			'title' => '👍👍 Status Approved ❤️❤️',
			'message' => '😀😀 Congratulations! Your status has been approved ❤️❤️',
			'image' => '',
			'icon' => '',
		];

		// Retrieve Firebase key from settings
		$setting = $em->getRepository(Settings::class)->findOneBy([]);
		$key = $setting ? $setting->getFirebasekey() : null;

		if ($key) {
			$this->send_notificationToken($tokens, $message, $key);
		} else {
			throw new \Exception('Firebase key not found in settings.');
		}
	}

	/**
	 * Generate an absolute URL for a given path.
	 */
	private function generateAbsoluteUrl(string $path): string
	{
		return $this->getParameter('kernel.project_dir') . '/' . ltrim($path, '/');
	}

    function send_notificationToken ($tokens, $message,$key)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $fields = array(
            'registration_ids'  => $tokens,
            'data'   => $message

            );
        $headers = array(
            'Authorization:key = '.$key,
            'Content-Type: application/json'
            );
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);  
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
       $result = curl_exec($ch);           
       if ($result === FALSE) {
           die('Curl failed: ' . curl_error($ch));
       }
       curl_close($ch);
       return $result;
    }
	public function api_upload(Request $request, $token) {

		$id = str_replace('"', '', $request->get("id"));
		$key = str_replace('"', '', $request->get("key"));
		$title = str_replace('"', '', $request->get("title"));
		$description = str_replace('"', '', $request->get("description"));

		$language_ids = str_replace('"', '', $request->get("language"));
		$language_list = explode("_", $language_ids);

		$categories_ids = str_replace('"', '', $request->get("categories"));
		$categories_list = explode("_", $categories_ids);

		$code = "200";
		$message = "Ok";
		$values = array();
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$em = $this->entityManager;
		$user = $em->getRepository(User::class)->findOneBy(array("id" => $id));
		if ($user == null) {
			throw new NotFoundHttpException("Page not found");
		}
		if (sha1($user->getPassword()) != $key) {
			throw new NotFoundHttpException("Page not found");
		}
		if ($user) {

			if ($this->getRequest()->files->has('uploaded_file')) {
				// $old_media=$user->getMedia();
				$file = $this->getRequest()->files->get('uploaded_file');
				$file_thum = $this->getRequest()->files->get('uploaded_file_thum');

				$media = new Media();
				$media->setFile($file);
				$media->upload($this->params->get('kernel.project_dir') . '/public/uploads');
				$em->persist($media);
				$em->flush();

				$media_thum = new Media();
				$media_thum->setFile($file_thum);
				$media_thum->upload($this->params->get('kernel.project_dir') . '/public/uploads');
				$em->persist($media_thum);
				$em->flush();

				$w = new Status();
				$w->setType("video");
				$w->setDownloads(0);
				//$w->setCategories($wallpaper->getCategories());
				//$w->setColors($wallpaper->getColors());

                if (!$user->getTrusted()) {
                    $w->setEnabled(false);
                    $w->setReview(true); 
                }else{
                    $w->setEnabled(true);
                    $w->setReview(false); 
                }
				$w->setComment(true);
				$w->setTitle($title);
				$w->setDescription($description);
				$w->setUser($user);
				$w->setVideo($media);
				$w->setMedia($media_thum);

				foreach ($language_list as $key => $id_language) {
					$language_obj = $em->getRepository(Language::class)->find($id_language);
					if ($language_obj) {
						$w->addlanguage($language_obj);
					}
				}
				foreach ($categories_list as $key => $id_category) {
					$category_obj = $em->getRepository(Category::class)->find($id_category);
					if ($category_obj) {
						$w->addCategory($category_obj);
					}
				}

				$em->persist($w);
				$em->flush();
                if ($user->getTrusted()) {
                    $transaction = new Transaction();
                    $setting = $em->getRepository(Settings::class)->findOneBy(array());
                    $transaction->setPoints($setting->getPoints("add".$w->getType()));
                    $transaction->setStatus($w);
                    $transaction->setUser($user);
                    $transaction->setType("add_".$w->getType());
                    $em->persist($transaction);
                    $em->flush();
                    $this->sendNotif($em,$w);
                }
			}
		}
		$error = array(
			"code" => $code,
			"message" => $message,
			"values" => $values,
		);
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($error, 'json');
		return new Response($jsonContent);
	}

    public function api_uploadGif(Request $request, $token) {

        $id = str_replace('"', '', $request->get("id"));
        $key = str_replace('"', '', $request->get("key"));
        $title = str_replace('"', '', $request->get("title"));
        $description = str_replace('"', '', $request->get("description"));

        $language_ids = str_replace('"', '', $request->get("language"));
        $language_list = explode("_", $language_ids);

        $categories_ids = str_replace('"', '', $request->get("categories"));
        $categories_list = explode("_", $categories_ids);

        $code = "200";
        $message = "Ok";
        $values = array();
        if ($token != $this->token) {
            throw new NotFoundHttpException("Page not found");
        }
        $em = $this->entityManager;
        $user = $em->getRepository(User::class)->findOneBy(array("id" => $id));
        if ($user == null) {
            throw new NotFoundHttpException("Page not found");
        }
        if (sha1($user->getPassword()) != $key) {
            throw new NotFoundHttpException("Page not found");
        }
        if ($user) {

            if ($this->getRequest()->files->has('uploaded_file')) {
                // $old_media=$user->getMedia();
                $file_thum = $this->getRequest()->files->get('uploaded_file');

                $media_thum = new Media();
                $media_thum->setFile($file_thum);
                $media_thum->upload($this->params->get('kernel.project_dir') . '/public/uploads');
                $em->persist($media_thum);
                $em->flush();

                $w = new Status();
                $w->setType("gif");
                $w->setDownloads(0);
                //$w->setCategories($wallpaper->getCategories());
                //$w->setColors($wallpaper->getColors());
                if (!$user->getTrusted()) {
                    $w->setEnabled(false);
                    $w->setReview(true); 
                }else{
                    $w->setEnabled(true);
                    $w->setReview(false); 
                }
                $w->setComment(true);
                $w->setTitle($title);
                $w->setDescription($description);
                $w->setUser($user);
                $w->setMedia($media_thum);

                foreach ($language_list as $key => $id_language) {
                    $language_obj = $em->getRepository(Language::class)->find($id_language);
                    if ($language_obj) {
                        $w->addlanguage($language_obj);
                    }
                }
                foreach ($categories_list as $key => $id_category) {
                    $category_obj = $em->getRepository(Category::class)->find($id_category);
                    if ($category_obj) {
                        $w->addCategory($category_obj);
                    }
                }

                $em->persist($w);
                $em->flush();


                if ($user->getTrusted()) {
                    $transaction = new Transaction();
                    $setting = $em->getRepository(Settings::class)->findOneBy(array());
                    $transaction->setPoints($setting->getPoints("add".$w->getType()));
                    $transaction->setStatus($w);
                    $transaction->setUser($user);
                    $transaction->setType("add_".$w->getType());
                    $em->persist($transaction);
                    $em->flush();
                    $this->sendNotif($em,$w);

                }
            }
        }
        $error = array(
            "code" => $code,
            "message" => $message,
            "values" => $values,
        );
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($error, 'json');
        return new Response($jsonContent);
    }
	public function api_uploadImage(Request $request, $token) {

		$id = str_replace('"', '', $request->get("id"));
		$key = str_replace('"', '', $request->get("key"));
		$title = str_replace('"', '', $request->get("title"));
		$description = str_replace('"', '', $request->get("description"));

		$language_ids = str_replace('"', '', $request->get("language"));
		$language_list = explode("_", $language_ids);

		$categories_ids = str_replace('"', '', $request->get("categories"));
		$categories_list = explode("_", $categories_ids);

		$code = "200";
		$message = "Ok";
		$values = array();
		if ($token != $this->token) {
			throw new NotFoundHttpException("Page not found");
		}
		$em = $this->entityManager;
		$user = $em->getRepository(User::class)->findOneBy(array("id" => $id));
		if ($user == null) {
			throw new NotFoundHttpException("Page not found");
		}
		if (sha1($user->getPassword()) != $key) {
			throw new NotFoundHttpException("Page not found");
		}
		if ($user) {

			if ($this->getRequest()->files->has('uploaded_file')) {
				// $old_media=$user->getMedia();
				$file_thum = $this->getRequest()->files->get('uploaded_file');

				$media_thum = new Media();
				$media_thum->setFile($file_thum);
				$media_thum->upload($this->params->get('kernel.project_dir') . '/public/uploads');
				$em->persist($media_thum);
				$em->flush();

				$w = new Status();
				$w->setType("image");
				$w->setDownloads(0);
				//$w->setCategories($wallpaper->getCategories());
				//$w->setColors($wallpaper->getColors());
                if (!$user->getTrusted()) {
                    $w->setEnabled(false);
                    $w->setReview(true); 
                }else{
                    $w->setEnabled(true);
                    $w->setReview(false); 
                }
				$w->setComment(true);
				$w->setTitle($title);
				$w->setDescription($description);
				$w->setUser($user);
				$w->setMedia($media_thum);

				foreach ($language_list as $key => $id_language) {
					$language_obj = $em->getRepository(Language::class)->find($id_language);
					if ($language_obj) {
						$w->addlanguage($language_obj);
					}
				}
				foreach ($categories_list as $key => $id_category) {
					$category_obj = $em->getRepository(Category::class)->find($id_category);
					if ($category_obj) {
						$w->addCategory($category_obj);
					}
				}

				$em->persist($w);
				$em->flush();

                if ($user->getTrusted()) {
                    $transaction = new Transaction();
                    $setting = $em->getRepository(Settings::class)->findOneBy(array());
                    $transaction->setPoints($setting->getPoints("add".$w->getType()));
                    $transaction->setStatus($w);
                    $transaction->setUser($user);
                    $transaction->setType("add_".$w->getType());
                    $em->persist($transaction);
                    $em->flush();
                    $this->sendNotif($em,$w);

                }
			}
		}
		$error = array(
			"code" => $code,
			"message" => $message,
			"values" => $values,
		);
		$encoders = array(new XmlEncoder(), new JsonEncoder());
		$normalizers = array(new ObjectNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($error, 'json');
		return new Response($jsonContent);
	}

	public function delete($id, Request $request) {
		$em = $this->entityManager;

		$video = $em->getRepository(Status::class)->find($id);
		if ($video == null) {
			throw new NotFoundHttpException("Page not found");
		}

		$form = $this->createFormBuilder(array('id' => $id))
			->add('id', 'hidden')
			->add('Yes', 'submit')
			->getForm();
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$media_old_video = null;
			$media_old_thumb = null;
			if ($video->getVideo() != null) {
				$media_old_video = $video->getVideo();
			}
			if ($video->getMedia() != null) {
				$media_old_thumb = $video->getMedia();
			}
			$em->remove($video);
			$em->flush();
			if ($media_old_thumb != null) {
				$media_old_thumb->delete($this->params->get('kernel.project_dir') . '/public/uploads');
				$em->remove($media_old_thumb);
				$em->flush();
			}
			if ($media_old_video != null) {
				$media_old_video->delete($this->params->get('kernel.project_dir') . '/public/uploads');
				$em->remove($media_old_video);
				$em->flush();
			}
			$this->addFlash('success', 'Operation has been done successfully');
			return $this->redirect($this->generateUrl('app_status_index'));
		}
		return $this->render('@AppBundle/Status/delete.html.twig', array("form" => $form->createView()));
	}

	public function index(Request $request, PaginatorInterface $paginator): Response
	{
		$em = $this->entityManager;
		$q = "";

		// Append search condition if query parameter "q" is present
		if ($request->query->has("q") && $request->query->get("q") !== "") {
			$searchTerm = $request->query->get("q");
			$q .= " AND i.title LIKE :searchTerm";
		}

		// Construct DQL query
		$dql = "SELECT i FROM App\AppBundle\Entity\Status i WHERE i.review = false" . $q . " ORDER BY i.created DESC";
		$query = $em->createQuery($dql);

		// Set parameter for search term if applicable
		if ($q !== "") {
			$query->setParameter('searchTerm', '%' . $searchTerm . '%');
		}

		// Paginate the results
		$statusList = $paginator->paginate(
			$query,
			$request->query->getInt('page', 1), // Default to page 1
			12 // Items per page
		);

		// Count total statuses
		$statusCount = $em->getRepository(Status::class)->count(['review' => false]);

		return $this->render('@AppBundle/Status/index.html.twig', [
			'status_list' => $statusList,
			'status_count' => $statusCount,
		]);
	}

	public function review(Request $request, $id) {
		$em = $this->entityManager;
		$status = $em->getRepository(Status::class)->findOneBy(array("id" => $id, "review" => true));
		if ($status == null) {
			throw new NotFoundHttpException("Page not found");
		}
		$form = $this->createForm(statusReviewType::class, $status);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$status->setReview(false);
			$status->setEnabled(true);
			$status->setCreated(new \DateTime());
			$em->persist($status);
			$em->flush();
			$this->addFlash('success', 'Operation has been done successfully');

            $transaction = new Transaction();
            $setting = $em->getRepository(Settings::class)->findOneBy(array());
            $transaction->setPoints($setting->getPoints("add".$status->getType()));
            $transaction->setStatus($status);
            $transaction->setUser($status->getUser());
            $transaction->setType("add_".$status->getType());
            $em->persist($transaction);
            $em->flush();
            
			return $this->redirect($this->generateUrl('app_home_notif_user_status', array("status_id" => $status->getId())));
		}
		return $this->render("@AppBundle/Status/review.html.twig", array("form" => $form->createView()));
	}
    public function reviewQuote(Request $request, $id) {
        $em = $this->entityManager;
        $status = $em->getRepository(Status::class)->findOneBy(array("id" => $id, "review" => true));
        if ($status == null) {
            throw new NotFoundHttpException("Page not found");
        }
        $form = $this->createForm(QuoteReviewType::class,$status);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $status->setReview(false);
            $status->setEnabled(true);
            $status->setCreated(new \DateTime());
            $em->persist($status);
            $em->flush();

            $transaction = new Transaction();
            $setting = $em->getRepository(Settings::class)->findOneBy(array());
            $transaction->setPoints($setting->getPoints("add".$status->getType()));
            $transaction->setStatus($status);
            $transaction->setUser($status->getUser());
            $transaction->setType("add_".$status->getType());
            $em->persist($transaction);
            $em->flush();

            $this->addFlash('success', 'Operation has been done successfully');
            return $this->redirect($this->generateUrl('app_home_notif_user_status', array("status_id" => $status->getId())));
        }
        return $this->render("@AppBundle/Status/quote_review.html.twig", array("status"=>$status,"form" => $form->createView()));
    }


	public function reviews(Request $request, PaginatorInterface $paginator): Response
	{
		$em = $this->entityManager;

		// Query to fetch statuses with reviews
		$dql = "SELECT w FROM App\AppBundle\Entity\Status w WHERE w.review = true ORDER BY w.created DESC";
		$query = $em->createQuery($dql);

		// Paginate the results
		$videos = $paginator->paginate(
			$query,
			$request->query->getInt('page', 1), // Default page is 1
			12 // Items per page
		);

		// Count total reviews
		$videosCount = $em->getRepository(Status::class)->count(['review' => true]);

		return $this->render('@AppBundle/Status/reviews.html.twig', [
			'videos' => $videos,
			'videos_count' => $videosCount,
		]);
	}


	public function view(Request $request, $id) {
		$em = $this->entityManager;
		$status = $em->getRepository(Status::class)->find($id);
		if ($status == null) {
			throw new NotFoundHttpException("Page not found");
		}
		return $this->render("@AppBundle/Status/view.html.twig", array("status" => $status));
	}
}
?>