<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,0&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: {
  colors: {primary:'#091426',secondary:'#0058be','on-primary':'#fff','on-secondary':'#fff','on-secondary-fixed':'#001a42','secondary-fixed':'#d8e2ff',surface:'#fbf8fa',background:'#fbf8fa','on-surface':'#1b1b1d','on-surface-variant':'#45474c',outline:'#75777d','outline-variant':'#c5c6cd','surface-container-lowest':'#fff','surface-container-low':'#f5f3f4','surface-container-high':'#eae7e9','error-container':'#ffdad6',error:'#ba1a1a'},
  fontFamily:{sans:['Inter','sans-serif'],'body-md':['Inter'],'label-md':['Inter'],'headline-md':['Inter'],'headline-lg':['Inter']},
  spacing:{xs:'4px',sm:'8px',md:'16px',lg:'24px',xl:'32px'},
  fontSize:{'body-md':['14px','20px'],'label-md':['12px','16px'],'headline-md':['20px','28px'],'headline-lg':['28px','36px']}
} } };
</script>
<link href="{{ asset('css/ediconnect.css') }}?v={{ filemtime(public_path('css/ediconnect.css')) }}" rel="stylesheet">
