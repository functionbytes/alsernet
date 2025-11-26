<div class="row mb-4">
   <div class="col-xl-12 col-lg-12 mb-4">
      <div class="questions-wrapper scrollable">
         <ul class="questions list-style-none">
            {foreach from=$questions item=question}
               <li class="question">
                  <div class="question-question">
                     <div class="question-content">
                        <h4 class="question-author">
                           <a href="">{$question.client_name}</a>
                           <span class="question-date">({$question.date_question})</span>
                        </h4>
                        <p>{$question.question}.</p>
                     </div>
                  </div>
                  {if isset($question.response_text) }
                        <div class="question-response">
                              <div class="comment">
                                 <p class=" text-qgray leading-7 text-normal">{$question.response_text}.</p>
                              </div>
                        </div>
                  {/if}
               </li>
            {/foreach}
         </ul>
      </div>
      <div class="questions-action">
            <button class="btn btn-primary review-new">Reviews</button>
      </div>

   </div>
</div>