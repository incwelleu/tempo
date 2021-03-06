<?php
require_once("rpcl/rpcl.inc.php");
require_once("include/fmstrong.php");
require_once("include/create_grid_column.php");

//Includes
use_unit("forms.inc.php");
use_unit("extctrls.inc.php");
use_unit("stdctrls.inc.php");
use_unit("components4phpfull/jttoolbar.inc.php");
use_unit("components4phpfull/jtsitetheme.inc.php");
use_unit("components4phpfull/jttabcontrol.inc.php");
use_unit("platinumgrid/jtplatinumgrid.inc.php");
use_unit('platinumgrid/lib/Spreadsheet_Excel_Writer.php');
use_unit("dbtables.inc.php");
use_unit("db.inc.php");
use_unit("components4phpfull/jtdivwindow.inc.php");
use_unit("components4phpfull/jtadvancededit.inc.php");
use_unit("components4phpfull/jtlookupcombobox.inc.php");
use_unit("components4phpfull/jtdatepicker.inc.php");
use_unit("imglist.inc.php");


//Class definition
class payments extends fmstrong
{
    public $cbShowCanceledInvoices = null;
    public $invoice_issued_id = null;
    public $sqlInvoice_issued_paid = null;
    public $dsInvoice_issued_paid = null;
    public $gridInvoice_issued_paid = null;
    public $paid_amt = null;
    public $lbPaid_amt = null;
    public $paid_dt = null;
    public $lbPaid_dt = null;
    public $lbPaid_by = null;
    public $paid_by = null;
    public $btnSavePayment = null;
    public $lbPayment_method = null;
    public $payment_method_id = null;
    public $lbBank_account = null;
    public $bank_account_id = null;
    public $sqlPayment_method = null;
    public $dsPayment_method = null;
    public $sqlCompany_bank_account = null;
    public $dsCompany_bank_account = null;
    public $SelectedKeysField = null;
    public $SiteTheme = null;
    public $btnPayments = null;
    public $TabPayments = null;
    public $gridPayments = null;
    public $sqlCompany = null;
    public $dsCompany = null;
    public $sqlPayments = null;
    public $dsPayments = null;
    public $winProcess = null;
    public $ImageList = null;
    public $active_tab = null;

    function paymentsCreate($sender, $params)
    {
      sw_style_selected($this);

      $this->ParameterPayments();
    }

    function ParameterPayments()
    {
      Global $SW_PAYMENTS_TAB, $language;
      $company_strong_id = sw_get_company_strong();

      $this->lbTitle->Caption = Title_Payments;
      $this->lbTitle->Visible = True;
      $this->winProcess->Hide();
      $this->cbShowCanceledInvoices->Caption = SW_SHOW_CANCELED_INVOICES;


      if (!$this->gridInvoice_issued_paid->inSession('')){
      	//Query with Payment method language
      	$this->sqlPayment_method->close();
      	$this->sqlPayment_method->SQL = "SELECT payment_method_id, {$language} as payment_method_name FROM payment_method ORDER BY {$language}";
      	$this->sqlPayment_method->open();

      	//Company bank account
      	$this->sqlCompany_bank_account->close();
      	$this->sqlCompany_bank_account->Params = $company_strong_id;
      	$this->sqlCompany_bank_account->open();

        $this->CreatedColumnGridPaid();
      }

      if (!$this->btnPayments->inSession('')){
        $this->TabPayments->TabIndex = 0;
        $this->TabPayments->Tabs = $SW_PAYMENTS_TAB;

        $this->TabChange();
      }
    }


    //Created column paid
    function CreatedColumnGridPaid()
    {
      $this->gridInvoice_issued_paid->Datasource->DataSet->close();
      $this->gridInvoice_issued_paid->Columns = array();

      $columns[] = sw_create_grid_column('paid_dt', $this->gridInvoice_issued_paid);
      $property = array(TYPE_COLUMN => 'JTPlatinumGridTextColumn',
                        CAPTION => SW_CAPTION_PAID_BY, WIDTH => 200,
                        DEFAULT_FILTER => 'Contains');
      $columns[] = sw_create_grid_column('paid_by', $this->gridInvoice_issued_paid, $property);
      $columns[] = sw_create_grid_column('paid_amt', $this->gridInvoice_issued_paid, array(CAN_EDIT => True));

      $lookupComboBox = array(DATASOURCE => $this->dsPayment_method, VALUE_FIELD=>'payment_method_id', TEXT_FIELD=>'payment_method_name');
      $property = array(TYPE_COLUMN => 'JTPlatinumGridTextColumn',
                        CAPTION => SW_CAPTION_PAYMENT_METHOD, WIDTH => 200,
                        DEFAULT_FILTER => 'Contains', TEXT_FIELD=>'payment_method_name',
                        EDITOR_TYPE => 'LookupComboBox', LOOKUP_COMBOBOX => $lookupComboBox);
      $columns[] = sw_create_grid_column('payment_method_id', $this->gridInvoice_issued_paid, $property);

      $lookupComboBox = array(DATASOURCE => $this->dsCompany_bank_account, VALUE_FIELD=>'bank_account_id', TEXT_FIELD=>'bank_account_name');
      $property = array(TYPE_COLUMN => 'JTPlatinumGridTextColumn',
                        CAPTION => SW_CAPTION_BANK_ACCOUNT, WIDTH => 200,
                        DEFAULT_FILTER => 'Contains', TEXT_FIELD=>'bank_account_name',
                        EDITOR_TYPE => 'LookupComboBox', LOOKUP_COMBOBOX => $lookupComboBox);
      $columns[] = sw_create_grid_column('bank_account_id', $this->gridInvoice_issued_paid, $property);

      $columns[] = sw_create_grid_column('created_dt', $this->gridInvoice_issued_paid);
      $columns[] = sw_create_grid_column('created_by_user_id', $this->gridInvoice_issued_paid);

      $property = array(TYPE_COLUMN => 'JTPlatinumGridBooleanColumn',
                        CAPTION => SW_CAPTION_ACCOUNTED_YN, DISPLAY_FORMAT => 'CheckBox',
                        TRUE_TEXT => SW_CAPTION_YES, FALSE_TEXT => SW_CAPTION_NO,
                        ALIGNMENT => 'agCenter', WIDTH => 90 );
      $columns[] = sw_create_grid_column('accounted_yn', $this->gridInvoice_issued_paid, $property);

      $this->gridInvoice_issued_paid->Columns = $columns;
      $this->gridInvoice_issued_paid->SortBy = 'paid_dt';
      $this->gridInvoice_issued_paid->Datasource->DataSet->open();
      $this->gridInvoice_issued_paid->Header->ShowFilterBar = True;
			$this->gridInvoice_issued_paid->Header->SimpleFilter = True;

      Define('COL_ACCOUNTED_PAID', $this->gridInvoice_issued_paid->findColumnByName('accounted_yn'));
    }

    function TabPaymentsJSChange($sender, $params)
    {
      echo $this->TabPayments->ajaxCall("TabChange");
        ?>
        //begin js
        gridPayments.selectAll(false);
        gridPayments._showWaitWindow();
        document.getElementById("cbShowCanceledInvoices_outer").style.display ='none';
        if (document.getElementById( "TabPayments" ).TabIndex == 0){
          document.getElementById("cbShowCanceledInvoices_outer").style.display ='block';
        }
        return false;
        //end
        <?php
    }

    function TabChange()
    {
      Global $SW_PAYMENTS_TAB;

      $this->TabPayments->TabIndex = $this->TabPayments->TabIndex;
      $this->active_tab->Value = $SW_PAYMENTS_TAB[$this->TabPayments->TabIndex][1];

      $this->sqlPayments->close();
      $this->gridPayments->Columns = array();
      foreach( $this->gridPayments->Columns as $Column )
      {
        $Column->Filter = "";
      }

      if ($this->active_tab->Value == 'TabUnpaid'){
        $this->ViewUnpaid();
      }else if ($this->active_tab->Value == 'TabPayments'){
        $this->ViewPayments();
      }

      $this->sqlPayments->open();
      $this->gridPayments->init();
    }


    function ViewUnpaid()
    {
       Global $SW_STASTUS_INVOICE_ISSUED_CD, $language;

      $enable = 1;
      //Create Button
      $items['btnFilter'] = array(btnFilter, 1, "7");
      $items['btnEdit'] = array(btnEdit, $enable, "3");
      $items['btnSave'] = array(btnSave, $enable, "5");
      $items['btnCancel'] = array(btnCancel, $enable, "4");
      $items['btnExportXLS'] = array(btnExportXLS, $enable, "8");
      $items['btnPaid'] = array(btnPaid, $enable);
      $items['btnUnPaid'] = array(btnUnPaid, $enable);
      $items['btnAmtSmall'] = array(btnAmtSmall, $enable);
      $items['btnZeroTotal'] = array(btnZeroTotal, $enable);
      $items['btnCreateCreditNote'] = array(btnCreateCreditNote, $enable);
      $items['btnSendEmailUnpaid'] = array(btnSendEmailUnpaid, $enable);
      $items['btnSendEmailUnpaid2'] = array(btnSendEmailUnpaid2, $enable);
      $this->btnPayments->Items = $items;

			$sql = "SELECT * FROM billing_entity ORDER BY billing_entity_name";
			$filterOption = sw_records_array($sql, array("billing_entity_id", "billing_entity_name"));
			$filterOption[0] = '';
      $property = array(CAN_EDIT => False,
												TYPE_COLUMN => 'JTPlatinumGridTextColumn',
                        CAPTION => SW_CAPTION_BILLING_ENTITY, WIDTH => 120,
                        DEFAULT_FILTER => 'Contains', TEXT_FIELD=>'billing_entity_name',
												FILTER_OPTIONS => $filterOption);
      $columns[] = sw_create_grid_column('billing_entity_name', $this->gridPayments, $property);

      $property = array(CAN_EDIT => False);
      $columns[] = sw_create_grid_column('invoice_number', $this->gridPayments, $property);
      $columns[] = sw_create_grid_column('invoice_dt', $this->gridPayments, $property);
      $columns[] = sw_create_grid_column('pending_amt', $this->gridPayments, $property);

			$sql = "SELECT payment_method_id, {$language} FROM payment_method ORDER BY payment_method.{$language}";
			$filterOption = sw_records_array($sql, array("payment_method_id", "{$language}"));
			$filterOption[0] = '';
      $property = array(CAN_EDIT => False,
												TYPE_COLUMN => 'JTPlatinumGridTextColumn',
                        CAPTION => SW_CAPTION_PAYMENT_METHOD, WIDTH => 120,
                        DEFAULT_FILTER => 'Contains', TEXT_FIELD=>"payment_method_name");
      $columns[] = sw_create_grid_column('payment_method_id', $this->gridPayments, $property);

      $property = array(CAN_EDIT => False);
      $columns[] = sw_create_grid_column('invoice_pdf', $this->gridPayments, $property);

      $property = array(CAN_EDIT => False, WIDTH => 120);
      $columns[] = sw_create_grid_column('client_name', $this->gridPayments, $property);

      $property = array(CAN_EDIT => False);
      $columns[] = sw_create_grid_column('username', $this->gridPayments, $property);


      $property = array(CAN_EDIT => False,
												TEXT_FIELD => 'accountManager',
												CAPTION => SW_CAPTION_ACCOUNT_MANAGER,
												WIDTH => 100);
      $columns[] = sw_create_grid_column('username', $this->gridPayments, $property);

      $property = array(CAN_EDIT => False);
      $columns[] = sw_create_grid_column('notes_memo', $this->gridPayments);
      $columns[] = sw_create_grid_column('subtotal_amt', $this->gridPayments, $property);
      $columns[] = sw_create_grid_column('tax_amt', $this->gridPayments, $property);
      $columns[] = sw_create_grid_column('other_income_amt', $this->gridPayments, $property);
      $columns[] = sw_create_grid_column('total_amt', $this->gridPayments, $property);
      $columns[] = sw_create_grid_column('paid_amt', $this->gridPayments, $property);
      $columns[] = sw_create_grid_column('tax_ident', $this->gridPayments, $property);

      $record = $SW_STASTUS_INVOICE_ISSUED_CD;
      $record[""] = "";
      $property = array(FILTER_OPTIONS => $record, CAN_EDIT => False);
      $columns[] = sw_create_grid_column('status_cd', $this->gridPayments, $property);

      $columns[] = sw_create_grid_column('link', $this->gridPayments);

      $this->gridPayments->Columns = $columns;
      $this->gridPayments->KeyField = 'invoice_issued_id';
      $this->gridPayments->SortBy = 'invoice_dt';
      $this->gridPayments->ReadOnly = False;
    }


    function ViewPayments()
    {
      $enable = 1;
      //Create Button
      $items['btnFilter'] = array(btnFilter, 1, "7");
      $this->btnPayments->Items = $items;

			$sql = "SELECT * FROM billing_entity ORDER BY billing_entity_name";
			$filterOption = sw_records_array($sql, array("billing_entity_id", "billing_entity_name"));
			$filterOption[0] = '';
      $property = array(CAN_EDIT => False,
												TYPE_COLUMN => 'JTPlatinumGridTextColumn',
                        CAPTION => SW_CAPTION_BILLING_ENTITY, WIDTH => 120,
                        DEFAULT_FILTER => 'Contains', TEXT_FIELD=>'billing_entity_name',
												FILTER_OPTIONS => $filterOption);
      $columns[] = sw_create_grid_column('billing_entity_name', $this->gridPayments, $property);

      $columns[] = sw_create_grid_column('invoice_number', $this->gridPayments);
      $columns[] = sw_create_grid_column('invoice_dt', $this->gridPayments);
      $columns[] = sw_create_grid_column('invoice_pdf', $this->gridPayments);
      $columns[] = sw_create_grid_column('tax_ident', $this->gridPayments);
      $columns[] = sw_create_grid_column('client_name', $this->gridPayments);
      $columns[] = sw_create_grid_column('total_amt', $this->gridPayments, array(SHOW_SUM => False));

      $columns[] = sw_create_grid_column('paid_dt', $this->gridPayments);

      $property = array(TYPE_COLUMN => 'JTPlatinumGridTextColumn',
                        CAPTION => SW_CAPTION_PAID_BY, WIDTH => 200,
                        DEFAULT_FILTER => 'Contains');
      $columns[] = sw_create_grid_column('paid_by', $this->gridPayments, $property);

      $columns[] = sw_create_grid_column('paid_amt', $this->gridPayments, array(CAN_EDIT => True));

      $property = array(TYPE_COLUMN => 'JTPlatinumGridTextColumn',
                        CAPTION => SW_CAPTION_PAYMENT_METHOD, WIDTH => 200,
                        DEFAULT_FILTER => 'Contains', TEXT_FIELD=>'payment_method_name');
      $columns[] = sw_create_grid_column('payment_method_id', $this->gridPayments, $property);

      $property = array(TYPE_COLUMN => 'JTPlatinumGridTextColumn',
                        CAPTION => SW_CAPTION_BANK_ACCOUNT, WIDTH => 200,
                        DEFAULT_FILTER => 'Contains', TEXT_FIELD=>'bank_account_name');
      $columns[] = sw_create_grid_column('bank_account_id', $this->gridPayments, $property);

      $columns[] = sw_create_grid_column('created_dt', $this->gridPayments);
      $columns[] = sw_create_grid_column('created_by_user_id', $this->gridPayments);

      $property = array(TYPE_COLUMN => 'JTPlatinumGridBooleanColumn',
                        CAPTION => SW_CAPTION_ACCOUNTED_YN, DISPLAY_FORMAT => 'CheckBox',
                        TRUE_TEXT => SW_CAPTION_YES, FALSE_TEXT => SW_CAPTION_NO,
                        ALIGNMENT => 'agCenter', WIDTH => 90 );
      $columns[] = sw_create_grid_column('accounted_yn', $this->gridPayments, $property);

      $columns[] = sw_create_grid_column('link', $this->gridPayments);

      $this->gridPayments->Columns = $columns;
      $this->gridPayments->KeyField = 'invoice_issued_paid_id';
      $this->gridPayments->SortBy = 'paid_dt desc, client_name, invoice_dt desc';
      $this->gridPayments->ReadOnly = True;
    }


    function btnPaymentsJSClick($sender, $params)
    {
        ?>
        //begin js
        var info = getEventTarget( event ).id.split( "_" );
        var toolButton = info[ 0 ];
        var toolButtonName = info[ 1 ];

        if (toolButton == 'btnPayments'){
          if (toolButtonName == 'btnFilter') {
            gridPayments.deselectAll();
            gridPayments._showWaitWindow();
            params = [0];
            <?php echo $this->gridPayments->ajaxCall('filter_grid', array(), array($this->gridPayments->Name)); ?>
          }
          else if ((toolButtonName == 'btnEdit') ||
                   (toolButtonName == 'btnPaid') ||
                   (toolButtonName == 'btnUnPaid') ||
                   (toolButtonName == 'btnAmtSmall') ||
                   (toolButtonName == 'btnZeroTotal') ||
									 (toolButtonName == 'btnCreateCreditNote') ||
									 (toolButtonName == 'btnSendEmailUnpaid') ||
									 (toolButtonName == 'btnSendEmailUnpaid2')){
            var keys = [];
            for (var row in gridPayments.SelectedCells) {
              if (typeof(gridPayments.SelectedCells[row]) != "function" &&
                 (gridPayments.SelectedCells[row] != '') &&
                 (gridPayments.SelectedCells[row] != null)) {
                 keys.push(gridPayments.getPrimaryKey(row));
                 if (toolButtonName == 'btnEdit'){ gridPayments.Edit(row); return false; }
                 if (toolButtonName == 'btnPaid' || toolButtonName == 'btnEdit'){ break; }
              }
            }
            if (findObj('SelectedKeysField').value = keys.join(',')){
              if (toolButtonName == 'btnAmtSmall') { return confirm("Esta seguro de eliminar las facturas seleccionadas?");}
              return true;
            } else return false;

          }
          else if (toolButtonName == 'btnCancel') { gridPayments.Cancel(); return false;}
          else if (toolButtonName == 'btnSave') { gridPayments.Post(); return false;}
        }
        //end
        <?php
    }

    function btnPaymentsClick($sender, $params)
    {
      list( $toolButton, $toolButtonName ) = explode( '_', $params[ 0 ] );

      if ($toolButtonName == "btnPaid")
      {
        $this->PaidInvoiceIssued();
      }else if ($toolButtonName == 'btnUnPaid') {
        $this->UnPaidInvoiceIssued();
      }else if ($toolButtonName == 'btnAmtSmall') {
        $this->AmountTooSmallInvoiceIssued();
      }else if ($toolButtonName == 'btnZeroTotal') {
        $this->ZeroTotalInvoiceIssued();
      }else if ($toolButtonName == 'btnCreateCreditNote') {
        $this->CreateCreditNote();
      }else if ($toolButtonName == 'btnSendEmailUnpaid') {
        $this->SendEmailUnpaid(KEYWORD_EMAIL_TEMPLATE_UNPAID);
      }else if ($toolButtonName == 'btnSendEmailUnpaid2') {
        $this->SendEmailUnpaid(KEYWORD_EMAIL_TEMPLATE_UNPAID . "2");
      }else if ($toolButtonName == 'btnExportXLS') {
        $filename = sw_clean_characters_spanish($this->TabPayments->Tabs[$this->TabPayments->TabIndex][0]);
        $this->gridPayments->exportGridToXLSDownload($filename . ".xls", $filename, True);
      }
    }


    function PaidInvoiceIssued()
    {
      if ($this->SelectedKeysField->Value){
				$this->openTable();

        $this->lbPaid_amt->Caption = SW_CAPTION_PAID_AMT;
        $this->lbPaid_by->Caption = SW_CAPTION_PAID_BY;
        $this->lbPaid_dt->Caption = SW_CAPTION_PAID_DT;
        $this->lbPayment_method->Caption = SW_CAPTION_PAYMENT_METHOD;
        $this->lbBank_account->Caption = SW_CAPTION_BANK_ACCOUNT;

        $record = sw_get_data_table("invoice_issued", "invoice_issued_id = {$this->SelectedKeysField->Value}");
        $this->paid_dt->date = date('Y-m-d');
        $this->paid_by->Text = '';
        $this->paid_amt->Text = ($record['total_amt'] - $record['paid_amt']);

        $this->winProcess->ActiveLayer = 0;
        $this->winProcess->Height = 180;
        $this->winProcess->Width = 540;
        $this->winProcess->Caption = btnPaid;
        $this->winProcess->ShowModal();
      }
    }


    function UnPaidInvoiceIssued()
    {
      if ($this->SelectedKeysField->Value){
        Global $connectionDB;

        $sql = "UPDATE invoice_issued
                SET status_cd = CASE WHEN status_cd != '" . SW_STATUS_IS_UNPAID . "' THEN '" . SW_STATUS_IS_UNPAID . "'
                                     WHEN accounted_yn THEN '" . SW_STATUS_IS_CLOSE . "' ELSE '" . SW_STATUS_IS_OPEN . "' END
                WHERE (invoice_issued_id in ({$this->SelectedKeysField->Value})) AND
                      (total_amt != paid_amt)";
        $connectionDB->DbConnection->BeginTrans();
        $connectionDB->DbConnection->execute($sql);
        $connectionDB->DbConnection->CompleteTrans();
        $this->gridPayments->writeSelectedCells(array());
        $this->invoice_issued_id->Value = 0;
      }
    }

    function AmountTooSmallInvoiceIssued()
    {
      if ($this->SelectedKeysField->Value){
        Global $connectionDB;

        $sql = "UPDATE invoice_issued
                      LEFT JOIN
                          (SELECT invoice_issued_id, SUM(paid_amt) AS paid_amt
                          FROM invoice_issued_paid
                          WHERE invoice_issued_id in ({$this->SelectedKeysField->Value})
                          GROUP BY invoice_issued_id) AS invoice_issued_paid
                       ON invoice_issued.invoice_issued_id = invoice_issued_paid.invoice_issued_id
                SET invoice_issued.paid_amt = CASE WHEN invoice_issued.status_cd != '" . SW_STATUS_TOO_SMALL . "' THEN invoice_issued.total_amt ELSE IfNull(invoice_issued_paid.paid_amt, 0) END,
                    invoice_issued.status_cd = CASE WHEN invoice_issued.status_cd != '" . SW_STATUS_TOO_SMALL . "' THEN '" . SW_STATUS_TOO_SMALL . "'
                                                    WHEN invoice_issued.accounted_yn THEN '" . SW_STATUS_IS_CLOSE . "' ELSE '" . SW_STATUS_IS_OPEN . "' END
                WHERE (invoice_issued.invoice_issued_id in ({$this->SelectedKeysField->Value}))";
        $connectionDB->DbConnection->BeginTrans();
        $connectionDB->DbConnection->execute($sql);
        $connectionDB->DbConnection->CompleteTrans();
        $this->gridPayments->writeSelectedCells(array());
        $this->invoice_issued_id->Value = 0;
      }
    }


    function ZeroTotalInvoiceIssued()
    {
      if ($this->SelectedKeysField->Value){
        Global $connectionDB;

        $sql = "SELECT SUM(total_amt-paid_amt) AS pending_amt
                FROM invoice_issued
                WHERE (invoice_issued_id in ({$this->SelectedKeysField->Value}))";
        $query = $connectionDB->DbConnection->execute($sql);
        $pending_amt = round($query->fields['pending_amt'], 2);
        if(!$pending_amt){
          $sql = "UPDATE invoice_issued
                      LEFT JOIN
                          (SELECT invoice_issued_id, SUM(paid_amt) AS paid_amt
                          FROM invoice_issued_paid
                          WHERE invoice_issued_id in ({$this->SelectedKeysField->Value})
                          GROUP BY invoice_issued_id) AS invoice_issued_paid
                       ON invoice_issued.invoice_issued_id = invoice_issued_paid.invoice_issued_id
                  SET invoice_issued.paid_amt = CASE WHEN invoice_issued.status_cd != '" . SW_STATUS_ZERO_TOTAL . "' THEN invoice_issued.total_amt ELSE IfNull(invoice_issued_paid.paid_amt, 0) END,
                      invoice_issued.status_cd = CASE WHEN invoice_issued.status_cd != '" . SW_STATUS_ZERO_TOTAL . "' THEN '" . SW_STATUS_ZERO_TOTAL . "'
                                                      WHEN invoice_issued.accounted_yn THEN '" . SW_STATUS_IS_CLOSE . "' ELSE '" . SW_STATUS_IS_OPEN . "' END
                  WHERE (invoice_issued.invoice_issued_id in ({$this->SelectedKeysField->Value}))";
          $connectionDB->DbConnection->BeginTrans();
          $connectionDB->DbConnection->execute($sql);
          $connectionDB->DbConnection->CompleteTrans();
          $this->gridPayments->writeSelectedCells(array());
          $this->invoice_issued_id->Value = 0;
        }
        else {
          $this->msgWarning->Value = "La suma del importe pendiente de las facturas debe ser cero ({$pending_amt})";
        }
      }
    }


    function CreateCreditNote()
    {
      if ($this->SelectedKeysField->Value){
        Global $connectionDB;

        $sql = "SELECT *
                FROM invoice_issued
                WHERE (invoice_issued_id in ({$this->SelectedKeysField->Value})) AND (NOT invoice_number like '{$_SESSION['settings']['serie_credit_note']}%')";

  			$connectionDB->Connected();
  			$query = new query();
  			$query->Database = $connectionDB->DbConnection;
  			$query->LimitStart = -1;
  			$query->LimitCount = -1;
  			$query->SQL = $sql;
  			$query->open();

  			While (!$query->EOF){

					$invoice_issued = $query->fieldbuffer;

					$invoice_issued_id = $this->createdTotalsCreditNote($invoice_issued);

					$this->createdLineCreditNote($invoice_issued, $invoice_issued_id);

          //Totalizo la factura
      		TotalInvoice($invoice_issued_id);

					$query->next();
      	}
    	}
		}

		// Create total Credit note
		function createdTotalsCreditNote($invoice_issued, $lastCreditNote){

    	Global $connectionDB;

			$whereLastCreditNote = "(invoice_issued.company_id = billing_entity.company_id) AND
									(billing_entity.billing_entity_id = setting.billing_entity_id) AND
									(invoice_issued.company_id = {$invoice_issued['company_id']}) AND
									(invoice_issued.invoice_number like CONCAT(setting.serie_credit_note, '%'))";
			$creditNote = sw_get_data_table("invoice_issued, billing_entity, setting", $whereLastCreditNote, "MAX(SUBSTRING_INDEX(invoice_issued.invoice_number, setting.serie_credit_note, -1)) AS credit_note_number");
			if (!$creditNote) {
					$creditNote['credit_note_number'] = 0;
			}

			++$creditNote['credit_note_number'];
			$record['invoice_number'] = $_SESSION['settings']['serie_credit_note'] . $creditNote['credit_note_number'];
			$record['invoice_dt'] = date('Y-m-d');
			$record['note'] = "Credit note for invoice #{$invoice_issued['invoice_number']} on {$invoice_issued['invoice_dt']}";

  		$sql = "INSERT INTO invoice_issued(company_id, created_by_user_id, company_client_id, payment_method_id, tax_type_key_id,
										invoice_number, invoice_dt, tax_ident, client_name,
										subtotal_amt, transport_amt, discount_amt, tax_amt,
										base_withholding_amt, withholding_rate_no, withholding_amt, other_income_amt,
										total_amt, created_dt, notes, status_cd)
							SELECT company_id, {$_SESSION['user_id']}, company_client_id, payment_method_id, tax_type_key_id,
										'{$record['invoice_number']}', '{$record['invoice_dt']}', tax_ident, client_name,
										subtotal_amt * -1, transport_amt * -1, discount_amt * -1, tax_amt * -1,
										base_withholding_amt * -1, withholding_rate_no, withholding_amt * -1, other_income_amt * -1,
										total_amt * -1, CURDATE(), '{$record['note']}', status_cd
							FROM invoice_issued
							WHERE invoice_issued_id = {$invoice_issued['invoice_issued_id']}";

  		$connectionDB->DbConnection->BeginTrans();
  		$connectionDB->DbConnection->execute($sql);
  		$connectionDB->DbConnection->CompleteTrans();

			return mysql_insert_id();
		}


		// Create total Credit note
		function createdLineCreditNote($invoice_issued, $invoice_issued_id){
			Global $connectionDB;

  		$sql = "INSERT INTO line_item(created_by_user_id, applies_to_user_id, invoice_issued_id,
											service_id, service_type_id, description, quantity_no, price_amt, total_amt,
											tax_rate_id, rate_no, created_dt, status_cd, status_previous_cd,
											frequency_no, commission_amt, company_id, future_invoice_dt,
											future_commission_amt, with_supplement_yn, sort_no)
   						SELECT {$_SESSION['user_id']}, applies_to_user_id, {$invoice_issued_id},
											service_id, service_type_id, description, quantity_no, price_amt * -1, total_amt * -1,
											tax_rate_id, rate_no, CURDATE(), status_cd, status_previous_cd,
											frequency_no, commission_amt * -1, company_id, CURDATE(),
											future_commission_amt * -1, with_supplement_yn, sort_no
							FROM line_item
							WHERE invoice_issued_id = {$invoice_issued['invoice_issued_id']}";

  		$connectionDB->DbConnection->BeginTrans();
  		$connectionDB->DbConnection->execute($sql);
  		$connectionDB->DbConnection->CompleteTrans();
		}


    function gridPaymentsRowData($sender, $params)
    {
      $fields = &$params[ 1 ];

      if ($this->active_tab->Value == 'TabUnpaid'){
        Global $SW_STASTUS_INVOICE_ISSUED_CD;
        $fields[ "status_cd" ] = $SW_STASTUS_INVOICE_ISSUED_CD[$fields[ "status_cd" ]];
      }

      if (isset($fields['invoice_pdf']) && $fields['link']){
        $fields['invoice_pdf'] = 'images/ftp/1px.gif';
        $file = utf8_decode($fields['link']);
        if (($file != "") && file_exists($file))
        {
          $fields['invoice_pdf'] = 'images/ftp/pdf.gif';
        }else $fields['link'] = "";
      }
    }

    function gridPaymentsSQL($sender, $params)
    {
      list( $sortSql, $sortFields, $filterSql ) = $params;

      $filterSql = str_replace("client_name", "invoice_issued.client_name", $filterSql);
      $filterSql = str_replace("tax_ident", "invoice_issued.tax_ident", $filterSql);

      $sql = $this->assigned_sql();

      //Filter Client_name
      $filterSql = $this->FilterClientName($filterSql);


      //Filter Client_name
      $filterSql = $this->FilterPaymentMethod($filterSql);

      if ($this->active_tab->Value == 'TabUnpaid'){
        $filter = "((ROUND(invoice_issued.total_amt, 2) - ROUND(invoice_issued.paid_amt, 2)) <> 0)";
        if (!$this->cbShowCanceledInvoices->Checked) {
          $filter .= " AND ((invoice_issued.status_cd = '" . SW_STATUS_IS_OPEN . "') OR (invoice_issued.status_cd = '" . SW_STATUS_IS_CLOSE . "'))";
        }
        else {
          $filter = "( " . $filter . " OR
                      ((invoice_issued.status_cd = '" . SW_STATUS_IS_UNPAID . "') OR
                          (invoice_issued.status_cd = '" . SW_STATUS_TOO_SMALL . "')) OR
                          (invoice_issued.status_cd = '" . SW_STATUS_ZERO_TOTAL . "'))";
        }
        $filterSql .= $filterSql ? " AND " : "";
        $filterSql .= $filter;
      }

      if (($filterSql) AND (sw_valid_sql($sql . " AND " . $filterSql)))
          $sql .= " AND " . $filterSql;

      $sortSql = " ORDER BY " . $sortSql;
      $this->sqlPayments->SQL = $sql . $sortSql;
    }


    function assigned_sql()
    {
      Global $language;

      $company_strong_id = sw_get_company_strong();
      if ($this->active_tab->Value == 'TabUnpaid'){
        $this->sqlPayments->TableName = 'invoice_issued';
        $sql = "SELECT invoice_issued.invoice_issued_id, invoice_issued.company_id, invoice_issued.company_client_id,
                invoice_issued.invoice_number, invoice_issued.invoice_dt, invoice_issued.tax_ident,
								invoice_issued.payment_method_id, payment_method.{$language} AS payment_method_name,
                CASE WHEN company.short_name IS NULL THEN invoice_issued.client_name ELSE company.short_name END AS client_name,
                invoice_issued.notes,
                invoice_issued.subtotal_amt, invoice_issued.transport_amt, invoice_issued.tax_amt,
                invoice_issued.withholding_amt, invoice_issued.other_income_amt, invoice_issued.total_amt,
                invoice_issued.paid_amt, (invoice_issued.total_amt - invoice_issued.paid_amt) AS pending_amt,
                invoice_issued.accounted_yn, invoice_issued.export_dt, invoice_issued.document_ident,
                invoice_issued.registered_in_acctg_software_dt, '' AS invoice_pdf, invoice_issued.link,
                invoice_issued.created_by_user_id, invoice_issued.created_dt, invoice_issued.status_cd,
                user.username, company_client.account_cd, billing_entity.billing_entity_name,
								vw_account_manager.username AS accountManager
              FROM invoice_issued
                  LEFT JOIN company_join_client ON invoice_issued.company_client_id = company_join_client.company_client_id
                  LEFT JOIN company ON company_join_client.company_id = company.company_id
                  LEFT JOIN user ON company.user_id = user.user_id
									LEFT JOIN billing_entity ON company.billing_entity_id = billing_entity.billing_entity_id
                  LEFT JOIN company_client ON invoice_issued.company_client_id = company_client.company_client_id
                  LEFT JOIN payment_method ON invoice_issued.payment_method_id = payment_method.payment_method_id
									LEFT JOIN vw_account_manager ON company.acct_manager_id = vw_account_manager.acct_manager_id
              WHERE (invoice_issued.company_id in ({$company_strong_id})) ";
      }else if ($this->active_tab->Value == 'TabPayments'){
        $sql = "SELECT invoice_issued.invoice_dt, invoice_issued.invoice_number, invoice_issued.tax_ident,
                  CASE WHEN company.short_name IS NULL THEN invoice_issued.client_name ELSE company.short_name END AS client_name,
                  invoice_issued.total_amt, '' AS invoice_pdf, invoice_issued.link,
                  invoice_issued_paid.invoice_issued_paid_id, invoice_issued_paid.paid_dt,
                  invoice_issued_paid.paid_by, invoice_issued_paid.paid_amt, invoice_issued_paid.bank_account_id,
                  invoice_issued_paid.payment_method_id, invoice_issued_paid.created_by_user_id,
                  invoice_issued_paid.created_dt, company_bank_account.bank_account_name,
                  payment_method.{$language} as payment_method_name, user.username, billing_entity.billing_entity_name
                FROM invoice_issued
                  INNER JOIN invoice_issued_paid ON invoice_issued.invoice_issued_id = invoice_issued_paid.invoice_issued_id
                  LEFT JOIN company_join_client ON invoice_issued.company_client_id = company_join_client.company_client_id
    							LEFT JOIN company ON company_join_client.company_id = company.company_id
									LEFT JOIN billing_entity ON company.billing_entity_id = billing_entity.billing_entity_id
                  LEFT JOIN company_bank_account ON invoice_issued_paid.bank_account_id = company_bank_account.bank_account_id
                  LEFT JOIN payment_method on invoice_issued_paid.payment_method_id = payment_method.payment_method_id
                  LEFT JOIN user ON invoice_issued_paid.created_by_user_id = user.user_id
                WHERE (invoice_issued.company_id in ({$company_strong_id}))";
      }

      return $sql;
    }


    function FilterClientName($filterSql)
    {
      $Column = $this->gridPayments->Columns[$this->gridPayments->findColumnByFieldName('client_name')];
      if ($Column->Filter !== "")
      {
        $search = "invoice_issued.client_name LIKE '%" . $Column->Filter . "%'";
        $filter = "({$search} OR company.short_name LIKE '%{$Column->Filter}%')";
        $filterSql = str_replace($search, $filter, $filterSql);
      }

      return $filterSql;
    }

    function FilterPaymentMethod($filterSql)
    {
		  Global $language;

      $Column = $this->gridPayments->Columns[$this->gridPayments->findColumnByFieldName('payment_method_id')];
      if ($Column->Filter !== "")
      {
        $search = "payment_method_name LIKE '%" . $Column->Filter . "%'";
        $filter = "(payment_method.{$language} LIKE '%" . $Column->Filter . "%')";
        $filterSql = str_replace($search, $filter, $filterSql);
      }

      return $filterSql;
    }

    function gridPaymentsSummaryData($sender, $params)
    {
      $Columna = &$params[1];
      $fields = &$params[2];
      $Total = number_format($fields['Sum'], 2, '.', '');
      $fields = array();
      $fields["&nbsp;&nbsp;&nbsp;" . $Columna->Caption] = $Total;
    }


    function btnSavePaymentClick($sender, $params)
    {
      $where = "invoice_issued_id in ({$this->SelectedKeysField->Value})";

      //Insert Service Agreement Paid
      $record_paid['invoice_issued_id']    = $this->SelectedKeysField->Value;
      $record_paid['payment_method_id']    = $this->payment_method_id->SelectedValue;
      $record_paid['bank_account_id']      = $this->bank_account_id->SelectedValue;
      $record_paid['paid_dt']              = $this->paid_dt->Date;
      $record_paid['paid_by']              = $this->paid_by->Text;
      $record_paid['paid_amt']              = $this->paid_amt->Text;
      $record_paid['created_by_user_id']    = $_SESSION['user_id'];
      $record_paid['created_dt']             = date('Y-m-d H:i:s');

      sw_insert_table("invoice_issued_paid", $record_paid);
      sw_UpdateInvoiceIssuedPaid($this->SelectedKeysField->Value);
    }


    function gridInvoice_issued_paidDelete($sender, $params)
    {
      $paid = implode(",", $params[ 0 ]);

      if ($fields = sw_get_data_table("invoice_issued_paid", "accounted_yn = 0 AND invoice_issued_paid_id in ({$paid})")){
        sw_delete_table("invoice_issued_paid", "accounted_yn = 0 AND invoice_issued_paid_id in ({$paid})");
        sw_UpdateInvoiceIssuedPaid($fields['invoice_issued_id']);
      }

      return true;
    }

    function gridInvoice_issued_paidSQL($sender, $params)
    {
      list( $sortSql, $sortFields, $filterSql ) = $params;

      GLOBAL $language;

      $sql = "SELECT invoice_issued_paid.*, company_bank_account.bank_account_name,
              payment_method.{$language} as payment_method_name, user.username
              FROM invoice_issued_paid
                   LEFT JOIN user ON invoice_issued_paid.created_by_user_id = user.user_id
                   LEFT JOIN company_bank_account ON invoice_issued_paid.bank_account_id = company_bank_account.bank_account_id
                   LEFT JOIN payment_method ON invoice_issued_paid.payment_method_id = payment_method.payment_method_id
              WHERE invoice_issued_paid.invoice_issued_id = '{$this->invoice_issued_id->Value}' ";

      if (($filterSql) AND (sw_valid_sql($sql . " AND " . $filterSql)))
          $sql .= " AND " . $filterSql;

      if ($sortSql) $sql .= " ORDER BY $sortSql";

      $sender->Datasource->DataSet->SQL = $sql;
    }


    function gridInvoice_issued_paidUpdate($sender, $params)
    {
      $paid = $params[ 0 ];
      $fields = $params[ 1 ];

      $fields['paid_amt'] = sw_convert_comma_point($fields['paid_amt']);

      sw_update_table("invoice_issued_paid", $fields, "invoice_issued_paid_id in ({$paid})");
      sw_UpdateInvoiceIssuedPaid($this->invoice_issued_id->Value);
    }


    function gridPaymentsJSSelect($sender, $params)
    {
        ?>
        //begin js
        var tab = document.getElementById( "TabPayments" ).TabIndex;
				var $pdf = sw_SearchStringInArray(sender.FEditableColumns, 'Name', 'invoice_pdf');
				var $link = sw_SearchStringInArray(sender.FEditableColumns, 'Name', 'link');

        document.getElementById("invoice_issued_id").value = 0

        //Tab Unpaid
        if (tab == 0){
          document.getElementById("invoice_issued_id").value = gridPayments.getPrimaryKey(row);
        }

        if (sender.id == 'gridPayments' && selected)
        {
          if (parseInt(col) === $pdf)
          {
            var cellValue = sender.getCellText(row, $link);
            if (cellValue){
              window.open(cellValue + "?random=" + (new Date()).getTime() + Math.floor(Math.random() * 1000000),"_blank","", false);
              sender.SelectedCol = 0;
            }
          }
        }

        //end
        <?php
    }


    function gridPaymentsUpdate($sender, $params)
    {
      $fields = &$params[ 1 ];
      $fields[ 'invoice_issued_id' ] = $params[ 0 ];

      sw_update_table("invoice_issued", $fields, "invoice_issued_id = " . $fields['invoice_issued_id']);
    }

    function cbShowCanceledInvoicesJSChange($sender, $params)
    {
        ?>
        //begin js
        gridPayments.Refresh();
        //end
        <?php
    }

    function gridInvoice_issued_paidShow($sender, $params)
    {
			$this->openTable();
    }

		function openTable()
		{
      Global $language;

			$where = "(line_item.company_id = company.company_id) AND (company.billing_entity_id = billing_entity.billing_entity_id) AND (line_item.invoice_issued_id = {$this->invoice_issued_id->Value})";
    	$billing_entity = sw_get_data_table("line_item, company, billing_entity", $where, "billing_entity.billing_entity_id, billing_entity.company_id");
			if ($billing_entity){
      	//Query with Payment method language
      	$this->sqlPayment_method->Active = False;
      	$this->sqlPayment_method->SQL = "SELECT payment_method_id, {$language} as payment_method_name FROM payment_method WHERE billing_entity_id = {$billing_entity['billing_entity_id']} ORDER BY {$language}";
      	$this->sqlPayment_method->Active = True;

      	//Company bank account
      	$this->sqlCompany_bank_account->close();
      	$this->sqlCompany_bank_account->Params = $billing_entity['company_id'];
      	$this->sqlCompany_bank_account->open();
			}
		}

    function SendEmailUnpaid($keyword)
    {
      if ($this->SelectedKeysField->Value){
      	Global $connectionDB, $email_to_cd_template;

      	$record_template = sw_get_data_table("email_template", "trigger_file_keyword = '{$keyword}'");
        $email_cd = $email_to_cd_template[$record_template['email_to_cd']];

        $sql = "SELECT company.*, invoice_number, invoice_issued_id, invoice_dt, invoice_issued.link, pending_amt, invoice_issued.notes
                FROM invoice_issued
											INNER JOIN company_join_client ON invoice_issued.company_client_id = company_join_client.company_client_id
											INNER JOIN company ON company_join_client.company_id = company.company_id
								     	INNER JOIN
												(SELECT invoice_issued.company_client_id, SUM(invoice_issued.total_amt - invoice_issued.paid_amt) AS pending_amt
												 FROM invoice_issued
												 WHERE invoice_issued_id in ({$this->SelectedKeysField->Value})
												 GROUP BY invoice_issued.company_client_id) AS invoice ON invoice_issued.company_client_id = invoice.company_client_id
                WHERE (invoice_issued_id in ({$this->SelectedKeysField->Value}))
								ORDER BY company.company_id, invoice_issued.invoice_number";

  			$connectionDB->Connected();
  			$query = new query();
  			$query->Database = $connectionDB->DbConnection;
  			$query->LimitStart = -1;
  			$query->LimitCount = -1;
  			$query->SQL = $sql;
  			$query->open();
        $company_id = 0;

				unset($_SESSION['TOTAL_PENDING']);

				While (!$query->EOF) {

					$invoice_issued = $query->fieldbuffer;

					$_SESSION['TOTAL_PENDING'] =  number_format(round(floatval($invoice_issued['pending_amt']), 2), 2, '.', '');

					$file = "Invoice {$invoice_issued['invoice_number']}";
					$sql = "SELECT contact.* FROM company INNER JOIN contact ON company.contact_list_id = contact.contact_list_id
									WHERE (company.company_id = {$invoice_issued['company_id']}) AND (contact.{$email_cd} = true) AND (LENGTH(TRIM(email)) != 0)";
					$record_contact = sw_records_array($sql, array('contact_id', 'email', 'first_name'));

					if ($keyword == "UNPAID2") {
						$sql = "SELECT provider_contact.provider_contact_id as contact_id, provider_contact.sw_email as email, provider_contact.first_name
									  FROM provider_contact
												 INNER JOIN company ON provider_contact.provider_contact_id = company.acct_manager_id OR provider_contact.provider_contact_id = company.accounting_provider_id
									  WHERE (company.company_id = {$invoice_issued['company_id']})";
						$record_contact = array_merge ( $record_contact, sw_records_array($sql, array('contact_id', 'email', 'first_name')));
					}

					$link = "http://" . str_replace('..',$_SERVER['SERVER_NAME'],$invoice_issued['link']);

					sw_created_email_draft($invoice_issued['company_id'], $record_template, $record_contact, $link, $file);

					$record['notes'] = $_SESSION['username'] . " " . strftime("%Y-%m-%d") . ", Send {$keyword} email \r\n" . $invoice_issued['notes'];

          sw_update_table("invoice_issued", $record, "invoice_issued_id = {$invoice_issued['invoice_issued_id']}");

					$query->next();
      	}

				unset($_SESSION['TOTAL_PENDING']);

    	}

		}


}

global $application;

global $payments;

//Creates the form
$payments=new payments($application);

//Read from resource file
$payments->loadResource(__FILE__);

header( "Content-type: text/html; charset=utf-8" );

//Shows the form
$payments->show();

?>