<?php 
$list=array();
foreach ($transactions as $key => $transaction) {
	$a["id"]=$transaction->getId();
	$a["label"]=$transaction->getLabel();
	$a["type"]=$transaction->getType();
	$a["points"]=$transaction->getPoints().' P';
	$a["amount"]=($transaction->getPoints())/$toCurrency." ".$currency;
	$a["enabled"]=true;
	$a["created"]=$view['time']->diff($transaction->getCreated());
	if ($transaction->getInvited()!=null) {
		$a["image"]=$transaction->getInvited()->getImage();
	}
	if ($transaction->getStatus()!=null) {
		if ($transaction->getStatus()->getType()!="quote") {
			$a["image"]=$app->getRequest()->getSchemeAndHttpHost() . "/" .$transaction->getStatus()->getMedia()->getLink();
		}else{
			$a["image"] = "none";
		}
	}
	$list[]=$a;
}
echo json_encode($list, JSON_UNESCAPED_UNICODE);

?>