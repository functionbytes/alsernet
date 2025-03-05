`<table  style="table-layout:fixed;background-color:#ffffff;" id="bodyTable">
	<tr>
		<td  style="padding-right:10px;padding-left:10px;" id="bodyCell">
			<table  style="max-width:600px;" class="wrapperBody">
				<tr>
					<td>
						<table  style="background-color:#FFFFFF;border-color:#fff; border-style:solid; border-width:0 1px 1px 1px;" class="tableCard">
							<tr>
								<td height="3" style="background-color:#081A28;font-size:1px;line-height:3px;" class="topBorder">&nbsp;</td>
							</tr>
							<tr align="center" valign="top" width="100%" style="margin-bottom: 20px;display: flex;">
								<td width="100%">
									<a href="{{ getUrl() }}" target="_blank">
										<img src="{{ getLogo() }}"  alt="" border="0" style="border:none;width: 100%;max-width: 300px;height:auto;display:block;padding-top: 60px;padding-bottom: 20px;padding-left: 0px;padding-right: 0px;">
									</a>
								</td>
							</tr>
							<tr style="display:flex;justify-content: center;">
								<td>
									<h2 style="color:#081A28; font-family:'Poppins', Helvetica, Arial, sans-serif; font-size:28px; font-weight:700; font-style:normal; letter-spacing:normal; line-height:36px; text-transform:none; text-align:center; padding:0; margin:0">
										VERIFICA TU CORREO
									</h2>
								</td>
							</tr>
							<tr>
								<td >
									<h4  style="color:#081A28; font-family:'Poppins', Helvetica, Arial, sans-serif; font-size:14px; font-weight:500; font-style:normal; letter-spacing:normal; line-height:24px; text-transform:none; text-align:center; padding:0; margin:0">
										Hola
										@if($firstname!=null)
											{{ $firstname }}
										@endif
									</h4>
								</td>
							</tr>
							<tr >
								<td>
									<table   width="100%" style="margin-top: 30px;display: flex;" >
										<tr>
											<td >
												<p style="color:#081A28; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:14px; font-weight:400; font-style:normal; letter-spacing:normal; line-height:22px; text-transform:none; text-align:center; padding:0; margin:0">
													¡Nos alegra que hayas decidido ser parte de nuestra comunidad!
												</p>
												<p style="color:#081A28; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:14px; font-weight:400; font-style:normal; letter-spacing:normal; line-height:22px; text-transform:none; text-align:center; padding:0; margin:0">
													Para poder continuar el proceso de registró debes confirmar tu cuenta de correo haciendo click en el siguiente botón.
												</p>
											</td>
										</tr>
									</table>
									<table  width="100%" style="margin-top: 30px;display: flex;">
										<tr>
											<td >
												<p style="color:#081A28; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:14px; font-weight:400; font-style:normal; letter-spacing:normal; line-height:22px; text-transform:none; text-align:center; padding:0; margin:0">
													También puedes utilizar este enlace<a href="{{ $url }}" target="_blank" style="color:#081A28"> {{ $url }}</a>
												</p>
											</td>
										</tr>
									</table>
									<table  width="100%" style="box-sizing:border-box;margin-top:30px;display:flex;justify-content: center;">
										<tr>
											<td>
												<table align="center" >
													<tr>
														<td align="center" class="ctaButton" style="background-color:#081A28;padding: 10px 20px;border-radius:4px">
															<a href="{{ $url }}" target="_blank" style="color:#FFFFFF; font-family:'Poppins', Helvetica, Arial, sans-serif; font-size:13px;   font-weight:600; font-style:normal;letter-spacing:1px; line-height:20px; text-transform:uppercase; text-decoration:none; display:block">
																VERIFICAR CUENTA
															</a>
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
		</td>
	</tr>
</table>


