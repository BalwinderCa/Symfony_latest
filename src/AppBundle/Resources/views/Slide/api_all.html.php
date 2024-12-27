<?php
$list = array();
foreach ($slides as $key => $slide) {
	$s = null;
	$s["id"] = $slide->getId();
	$s["title"] = $slide->getTitle();
	$s["type"] = $slide->getType();
	$s["image"] = $this['imagine']->filter($view['assets']->getUrl($slide->getMedia()->getLink()), 'slide_thumb');
	if ($slide->getType() == 3 && $slide->getStatus() != null) {
		$status = $slide->getStatus();
        $a = array();

        $a["id"]=$status->getId();
        $a["kind"]=$status->getType();
        $a["title"]=$status->getTitle();
        $a["description"]=$status->getDescription();
        $a["review"]=$status->getReview();
        $a["comment"]=$status->getComment();
        $a["comments"]=sizeof($status->getComments());
        $a["downloads"]=$status->getDownloads();
        $a["views"]=$status->getViews();
        $a["font"]=$status->getFont();
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
		$s["status"] = $a;
	} elseif ($slide->getType() == 1 && $slide->getCategory() != null) {
		$c["id"] = $slide->getCategory()->getId();
		$c["title"] = $slide->getCategory()->getTitle();
		$c["image"] = $this['imagine']->filter($view['assets']->getUrl($slide->getCategory()->getMedia()->getLink()), 'category_thumb_api');
		$s["category"] = $c;
	} elseif ($slide->getType() == 2 && $slide->getUrl() != null) {
		$s["url"] = $slide->getUrl();
	}
	$list[] = $s;
}
echo json_encode($list, JSON_UNESCAPED_UNICODE);

?>