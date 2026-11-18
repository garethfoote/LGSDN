( () => {
 'use strict';
 document.querySelectorAll( '[data-spotlight-carousel]' ).forEach( ( carousel ) => {
  const slides = Array.from( carousel.querySelectorAll( '.homepage-spotlight__slide' ) );
  const controls = carousel.querySelector( '.homepage-spotlight__controls' );
  const buttons = Array.from( controls.querySelectorAll( 'button' ) );
  let activeIndex = 0;
  const viewport = carousel.querySelector( '.homepage-spotlight__viewport' );
  let gesture = null;
  let suppressClick = false;
  const show = ( index ) => {
   activeIndex = index;
   carousel.style.setProperty( '--spotlight-index', index );
   slides.forEach( ( slide, i ) => {
    slide.classList.toggle( 'is-active', i === index );
    slide.inert = i !== index;
    slide.setAttribute( 'aria-hidden', String( i !== index ) );
    buttons[i].setAttribute( 'aria-pressed', String( i === index ) );
    buttons[i].classList.toggle( 'is-visible', i === index );
   } );
  };
  buttons.forEach( ( button, index ) => {
   button.addEventListener( 'click', () => show( index ) );
   button.addEventListener( 'keydown', ( event ) => {
    if ( ! [ 'ArrowLeft', 'ArrowRight', 'Home', 'End' ].includes( event.key ) ) return;
    event.preventDefault();
    const next = event.key === 'Home' ? 0 : event.key === 'End' ? slides.length - 1 : ( index + ( event.key === 'ArrowRight' ? 1 : -1 ) + slides.length ) % slides.length;
    show( next ); buttons[next].focus();
   } );
  } );
  viewport.addEventListener( 'pointerdown', ( event ) => {
   if ( ! event.isPrimary || event.button !== 0 || gesture ) return;
   gesture = { id: event.pointerId, x: event.clientX, y: event.clientY, dx: 0, dragging: false };
   suppressClick = false;
  } );
  viewport.addEventListener( 'pointermove', ( event ) => {
   if ( ! gesture || gesture.id !== event.pointerId ) return;
   const dx = event.clientX - gesture.x;
   const dy = event.clientY - gesture.y;
   if ( ! gesture.dragging ) {
    if ( Math.max( Math.abs( dx ), Math.abs( dy ) ) < 8 ) return;
    if ( Math.abs( dy ) > Math.abs( dx ) ) { gesture = null; return; }
    gesture.dragging = true;
    viewport.setPointerCapture( event.pointerId );
    carousel.classList.add( 'is-dragging' );
   }
   event.preventDefault();
   gesture.dx = dx;
   const atEdge = ( activeIndex === 0 && dx > 0 ) || ( activeIndex === slides.length - 1 && dx < 0 );
   carousel.style.setProperty( '--spotlight-drag', ( atEdge ? dx * 0.25 : dx ) + 'px' );
  } );
  const finish = ( event ) => {
   if ( event.type === 'lostpointercapture' && event.target !== viewport ) return;
   if ( ! gesture || gesture.id !== event.pointerId ) return;
   const { dx, dragging } = gesture;
   gesture = null;
   carousel.classList.remove( 'is-dragging' );
   carousel.style.removeProperty( '--spotlight-drag' );
   if ( dragging ) {
    suppressClick = true;
    const threshold = Math.min( 80, viewport.clientWidth * 0.15 );
    const step = event.type !== 'pointercancel' && Math.abs( dx ) > threshold ? ( dx < 0 ? 1 : -1 ) : 0;
    show( Math.max( 0, Math.min( slides.length - 1, activeIndex + step ) ) );
   }
   if ( viewport.hasPointerCapture( event.pointerId ) ) viewport.releasePointerCapture( event.pointerId );
  };
  window.addEventListener( 'pointerup', finish );
  window.addEventListener( 'pointercancel', finish );
  viewport.addEventListener( 'lostpointercapture', finish );
  viewport.addEventListener( 'click', ( event ) => {
   if ( suppressClick ) { event.preventDefault(); event.stopPropagation(); suppressClick = false; }
  }, true );
  viewport.addEventListener( 'dragstart', ( event ) => event.preventDefault() );
  show( 0 );
  carousel.classList.add( 'is-enhanced' );
  carousel.setAttribute( 'aria-roledescription', 'carousel' );
  slides.forEach( ( slide ) => slide.setAttribute( 'aria-roledescription', 'slide' ) );
  controls.hidden = false;
 } );
} )();
