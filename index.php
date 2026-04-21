<?php use ANTHeader\ANTNavLinkTag;
use function ANTHeader\ANTNavFavicond;
use function ANTHeader\create_head2;

function htmlEncodeMinimal(string $value): string
{
    $html = str_replace('<', '&lt;',
            str_replace('&', '&amp;',
                    "$value"));
    return ($html);
}

require_once "{$_SERVER['DOCUMENT_ROOT']}/require/createHead2.php";
create_head2($title = 'SVGViewer', ['base' => '/svgViewer/',
], [new ANTNavLinkTag('stylesheet', ['style.css']),
], [ANTNavFavicond('https://ANTRequest.nl', $title, true)]) ?>
<div class=divs>
    <div>
        <noscript class=replaceAble>javascript must be enabled</noscript>
    </div>
    <label for=note-body>SVG Text:</label>
    <div>
        <textarea id=note-body placeholder=Body rows=26><?= htmlEncodeMinimal(
                    "<svg viewBox=\"0 0 64 64\" xmlns=\"http://www.w3.org/2000/svg\">\n\x20\x20" .
                    "\x20\x20<circle r=\"30\" cx=\"32\" cy=\"32\" fill=\"red\"/>\n</svg>") ?></textarea>
    </div>
    <div>
        <button id=download.svg type=button>Download (SVG)</button>
        <button id=download.png type=button>Download (PNG)</button>
    </div>
    <script type=module>
        const DomParser = new DOMParser, viewBoxedRegexp = /(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/,
            textarea = document.getElementById('note-body'), xmlStringifier = new XMLSerializer;
        textarea.addEventListener('input', function () {
            const {value} = this, {width, height, blobject} = parseBlobData(value);
            if (width && height) {
                localStorage.setItem('svgViewer/blobject', value);
                const src = URL.createObjectURL(blobject),
                    img = Object.assign(new Image(width, height), {
                        className: 'replaceAble',
                        alt: 'Your Result', src,
                    });
                document.querySelector('.replaceAble')?.replaceWith(img);
                img.decode().then(() => img).finally(() => URL.revokeObjectURL(src));
            }
        });
        const blobject = localStorage.getItem('svgViewer/blobject');
        if (blobject) {
            textarea.value = blobject;
        }
        textarea.dispatchEvent(new CustomEvent('input')); //blobjectUrl
        document.getElementById('download.svg').addEventListener('click', function () {
            const blob = new Blob(Array.of(textarea.value), {type: 'image/svg+xml'}),
                href = URL.createObjectURL(blob), a = document.createElement('a');
            document.body.append(Object.assign(a, {href, download: Date()}));
            wait(15_0000).then(() => void URL.revokeObjectURL(href));//.then(() => void a.remove());
            a.click();
            a.remove();
        });
        document.getElementById('download.png').addEventListener('click', function () {
            const {value} = textarea, {width, height, svgDocument} = parseBlobData(value);
            if (width && height) {
                svgDocument.setAttribute('xmlns', "http://www.w3.org/2000/svg");
                console.log(xmlStringifier.serializeToString(svgDocument));
                const blobject = new Blob(Array.of(xmlStringifier.serializeToString(svgDocument)),
                        {type: 'image/svg+xml'}), src = URL.createObjectURL(blobject),
                    img = Object.assign(new Image(width, height), {
                        className: 'replaceAble',
                        alt: 'Your Result', src,
                    }), canvas = new OffscreenCanvas(width, height);
                img.decode().then(() => createImageBitmap(img)).then(bitmap => {
                    canvas.getContext('2d').drawImage(bitmap, 0, 0);
                    bitmap.close();
                    return canvas.convertToBlob().then(blobjectPNG => {
                        const href = URL.createObjectURL(blobjectPNG), a = document.createElement('a');
                        document.body.append(Object.assign(a, {href, download: Date()}));
                        a.click();
                        a.remove();
                        return wait(10_000).then(() => void URL.revokeObjectURL(href));
                    });
                }).finally(() => URL.revokeObjectURL(src));
            }
        });

        function wait(millis, value = undefined) {
            return (new Promise(resolve => setTimeout(resolve, millis))).then(() => value);
        }

        function parseBlobData(value) {
            const svgDocument = DomParser.parseFromString(value, 'image/svg+xml').documentElement,
                viewBoxAttr = svgDocument.getAttribute('viewBox'), viewBoxed = viewBoxedRegexp.exec(viewBoxAttr);
            const width = +(viewBoxed?.[3] ?? svgDocument.getAttribute('width') ?? NaN),
                height = +(viewBoxed?.[4] ?? svgDocument.getAttribute('height') ?? NaN);
            return {width, height, svgDocument, blobject: new Blob(Array.of(textarea.value), {type: 'image/svg+xml'})};
        }
    </script>
</div>
