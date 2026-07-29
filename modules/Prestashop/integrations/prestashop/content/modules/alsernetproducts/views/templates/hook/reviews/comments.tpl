<div class="row mb-4">
   <div class="col-xl-4 col-lg-5 mb-4">
      <div class="ratings-wrapper">
         <div class="avg-rating-container">
            <h4 class="avg-mark font-weight-bolder ls-50">{$average}</h4>
            <div class="avg-rating">
               <p class="text-dark mb-1">{l s='Average mark' mod='alsernetproducts'}</p>
               <div class="ratings-container">
                  <div class="ratings-full">
                     <span class="ratings" style="width: {$calification}%;"></span>
                     <span class="tooltiptext tooltip-top">{$average}</span>
                  </div>
                  <a href="#" class="rating-reviews">({$number} {l s='Reviews' mod='alsernetproducts'})</a>
               </div>
            </div>
         </div>
         <div class="ratings-list">
            <div class="ratings-container">
               <div class="ratings-full">
                  <span class="ratings" style="width: 100%;"></span>
                  <span class="tooltiptext tooltip-top">5 estrellas</span>
               </div>
               <div class="progress-bar progress-bar-sm ">
                  <span style="width: {$fiveStarsPercent}%;"></span>
               </div>
               <div class="progress-value">
                  <mark>{$fiveStarsPercent}%</mark>
               </div>
            </div>
            <div class="ratings-container">
               <div class="ratings-full">
                  <span class="ratings" style="width: 80%;"></span>
                  <span class="tooltiptext tooltip-top">4 estrellas</span>
               </div>
               <div class="progress-bar progress-bar-sm ">
                  <span style="width: {$fourStarsPercent}%;"></span>
               </div>
               <div class="progress-value">
                  <mark>{$fourStarsPercent}%</mark>
               </div>
            </div>
            <div class="ratings-container">
               <div class="ratings-full">
                  <span class="ratings" style="width: 60%;"></span>
                  <span class="tooltiptext tooltip-top">3 estrellas</span>
               </div>
               <div class="progress-bar progress-bar-sm ">
                  <span style="width: {$threeStarsPercent}%;"></span>
               </div>
               <div class="progress-value">
                  <mark>{$threeStarsPercent}%</mark>
               </div>
            </div>
            <div class="ratings-container">
               <div class="ratings-full">
                  <span class="ratings" style="width: 40%;"></span>
                  <span class="tooltiptext tooltip-top">2 estrellas</span>
               </div>
               <div class="progress-bar progress-bar-sm ">
                  <span style="width: {$twoStarsPercent}%;"></span>
               </div>
               <div class="progress-value">
                  <mark>{$twoStarsPercent}%</mark>
               </div>
            </div>
            <div class="ratings-container">
               <div class="ratings-full">
                  <span class="ratings" style="width: 20%;"></span>
                  <span class="tooltiptext tooltip-top">1 estrella</span>
               </div>
               <div class="progress-bar progress-bar-sm ">
                  <span style="width: {$oneStarPercent}%;"></span>
               </div>
               <div class="progress-value">
                  <mark>{$oneStarPercent}%</mark>
               </div>
            </div>
            <div class="ratings-container">
               <div class="ratings-full">
                  <span class="ratings" style="width: 0%;"></span>
                  <span class="tooltiptext tooltip-top">0 estrellas</span>
               </div>
               <div class="progress-bar progress-bar-sm ">
                  <span style="width: {$zeroStarPercent}%;"></span>
               </div>
               <div class="progress-value">
                  <mark>{$zeroStarPercent}%</mark>
               </div>
            </div>
         </div>
         <div class="ratings-action">
            <button class="btn btn-primary review-new">{l s='Reviews' mod='alsernetproducts'}</button>
         </div>
      </div>
   </div>
   <div class="col-xl-8 col-lg-7 mb-4">
      <div class="comments-wrapper scrollable">
         <ul class="comments list-style-none">
            {foreach from=$reviews item=review}
            <li class="comment">
               <div class="comment-body">
                  <div class="comment-content">
                     <h4 class="comment-author">
                        <a href="">{$review.nick}</a>
                        <span class="comment-date">({$review.date})</span>
                     </h4>
                     <div class="ratings-container comment-rating">
                        <div class="ratings-full">
                           <span class="ratings" style="width: {($review.stars*10)}%;"></span>
                           <span class="tooltiptext tooltip-top"></span>
                        </div>
                     </div>
                     <p>{$review.comment}.</p>
                  </div>
               </div>
            </li>
            {/foreach}
         </ul>
      </div>
   </div>
</div>