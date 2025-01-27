<table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout:fixed;background-color:#F9F9F9;" id="bodyTable">
    <tr>
        <td align="center" valign="top" style="padding-right:10px;padding-left:10px;" id="bodyCell">
            <table align="center" border="0" cellspacing="0" cellpadding="0" style="width:600px;" width="600">
                <tr>
                    <td>
                        <table border="0" cellpadding="0" cellspacing="0" style="max-width:600px;" width="100%" class="wrapperWebview">
                            <tr>
                                <td align="center" valign="top">
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td align="right" valign="middle" style="padding-top:20px;padding-right:0px;" class="webview">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <table style="background-color:#FFFFFF;border-color:#fff; border-style:solid; border-width:0 1px 1px 1px;" class="tableCard">
                            <tr>
                                <td height="3" style="background-color:#081A28;font-size:1px;line-height:3px;" class="topBorder">&nbsp;</td>
                            </tr>
                            <tr>
                                <td align="center" valign="top" style="padding-top:20px;">
                                    <a href="{{ $slack }}" target="_blank">
                                        <img src="{{ getLogo() }}" alt="Logo" border="0" style="border:none;width: 100%;max-width: 300px;height:auto;display:block;padding-top: 20px;padding-bottom: 40px;padding-left: 0px;padding-right: 0px;">
                                    </a>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding: 10px 20px 40px 20px;">
                                    <table width="100%">
                                        <tr>
                                            <td style="text-align: center;">
                                                <h2 style="color:#081A28; font-family:'Poppins', Helvetica, Arial, sans-serif; font-size:28px; font-weight:700; font-style:normal; letter-spacing:normal; line-height:36px; text-transform:none; text-align:center; padding:0; margin:0">
                                                    REPORTE FACTURACIÓN
                                                </h2>
                                            </td>
                                        </tr>
                                    </table>

                                    <table width="100%" style="margin-top: 10px;">
                                        <tr>
                                            <td>
                                                <p style="color:#081A28; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:14px; font-weight:400; font-style:normal; letter-spacing:normal; line-height:22px; text-transform:none; text-align:center; padding:0; margin:0">
                                                    Nos complace informarte que se ha generado la factura del distribuidor <strong>{{ $distributor }}</strong> identificado con NIT <strong>{{ $nit }}</strong>.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                    <table width="100%" style="margin-top: 10px;">
                                        <tr>
                                            <td>
                                                <p style="color:#081A28; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:14px; font-weight:400; font-style:normal; letter-spacing:normal; line-height:22px; text-transform:none; text-align:center; padding:0; margin:0">
                                                    La fecha de facturacion es del  {{ $from_at }} a {{ $to_at }}.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                    <table width="100%" style="margin-top: 20px;">
                                        <tr>
                                            <td align="center">
                                                <a href="{{ getUrl() }}/accounting/invoices/view/{{ $slack }}" target="_blank" style="background-color: #081A28; padding: 10px 20px;border-radius:4px;color:#FFFFFF;
                                                    font-family:'Poppins', Helvetica, Arial, sans-serif;
                                                    font-size:13px;
                                                    font-weight:600;
                                                    font-style:normal;
                                                    letter-spacing:1px;
                                                    line-height:20px;
                                                    text-transform:uppercase;
                                                    text-decoration:none;
                                                    display:block;
                                                    text-align: center;
                                                    width: max-content;
                                                ">
                                                    VERIFICAR FACTURA
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                </td>
                            </tr>
                        </table>

                        <table border="0" cellpadding="0" cellspacing="0" style="max-width:600px;" width="100%" class="wrapperWebview">
                            <tr>
                                <td align="center" valign="top">
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td align="right" valign="middle" style="padding-top:20px;padding-right:0px;" class="webview">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>