<?php 
function truncate($text, $length=38)
   {
      $trunc = (strlen($text)>$length)?true:false;
      if($trunc)
         return substr($text, 0, $length).'...';
      else
         return $text;
   }
$list=array();
foreach ($videos as $key => $status) {
	$a = array();

	$a["id"]=$status->getId();
	$a["kind"]=$status->getType();
	$a["title"]=$status->getTitle();
	$a["description"]=$status->getDescription();
	$a["review"]=$status->getReview();
	$a["comment"]=$status->getComment();
	$a["comments"]=sizeof($status->getComments());
	$a["downloads"]=$status->getDownloads();
	$a["font"]=$status->getFont();
	$a["views"]=$status->getViews();
	$a["user"]=$status->getUser()->getName();
	$a["userid"]=$status->getUser()->getId();
	$a["userimage"]=$status->getUser()->getImage();
	if ($status->getType()!="quote") {
		if ($status->getVideo()) {
			$a["type"]=$status->getVideo()->getType();
			$a["extension"]=$status->getVideo()->getExtension();
		}else{
			$a["type"]=$status->getMedia()->getType();
			$a["extension"]=$status->getMedia()->getExtension();
		}
		$a["thumbnail"]= $this['imagine']->filter($view['assets']->getUrl($status->getMedia()->getLink()), 'status_thumb_api');
		if ($status->getVideo()) {
			if ($status->getVideo()->getEnabled()) {
				$a["original"] = $app->getRequest()->getSchemeAndHttpHost() . "/" .$status->getVideo()->getLink();
			}else{
				$a["original"] = $status->getVideo()->getLink();
			}	
		}else{
			$a["original"]=$app->getRequest()->getSchemeAndHttpHost() . "/" . $status->getMedia()->getLink();
		}
	}else{
		$a["color"]=$status->getColor();
	}
	$a["created"]=$view['time']->diff($status->getCreated());
	$a["tags"]=$status->getTags();
	$a["like"]=$status->getLike();
	$a["love"]=$status->getLove();
	$a["woow"]=$status->getWoow();
	$a["angry"]=$status->getAngry();
	$a["sad"]=$status->getSad();
	$a["haha"]=$status->getHaha();

	$list[]=$a;
}
echo json_encode($list, JSON_UNESCAPED_UNICODE);?>