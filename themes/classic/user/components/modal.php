<?php
declare(strict_types=1);
// Reusable modal helper for Classic User Panel
?>
<div id="classicModal" style="display:none;position:fixed;inset:0;background:rgba(36,20,61,.5);z-index:80;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:22px;max-width:480px;width:100%;padding:22px;box-shadow:0 24px 60px rgba(36,20,61,.22)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <strong id="classicModalTitle" style="font-size:17px">Details</strong>
      <button type="button" onclick="document.getElementById('classicModal').style.display='none'" style="background:#F4EFFC;border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-weight:700">&times;</button>
    </div>
    <div id="classicModalBody" style="font-size:14px;color:#777083;line-height:1.6"></div>
  </div>
</div>
