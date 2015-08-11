{literal}
<script type="text/javascript">
  //FIXME This assumes that order of the fields is the same as on install,
  //which might not be the case.
  CRM.$("#Representative_Details table tbody tr:nth-child(4)").hide();
  if (CRM.$("#Representative_Details table tbody tr:nth-child(3) :selected").text() != '') {
    CRM.$("#Representative_Details table tbody tr:nth-child(4)").show();
  }
  CRM.$("#Representative_Details table tbody tr:nth-child(3)").change( function() {
    if (CRM.$("#Representative_Details table tbody tr:nth-child(3) :selected").text() != '') {
      CRM.$("#Representative_Details table tbody tr:nth-child(4)").show();
    } else {
      CRM.$("#Representative_Details table tbody tr:nth-child(4)").hide();
    }
  });
</script>
{/literal}
