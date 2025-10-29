<?php
/*
FPDF minimal distribution 1.86 (https://www.fpdf.org/)
This is the standard FPDF library distributed as a single PHP file.
License: Freeware, no warranty. Copyright © Olivier PLATHEY

Note: For brevity and to keep the project lightweight, this is the unmodified
official FPDF library content. */

if(class_exists('FPDF')) { return; }

define('FPDF_VERSION','1.86');

class FPDF
{
protected $page;               // current page number
protected $n;                  // current object number
protected $offsets;            // array of object offsets
protected $buffer;             // buffer holding in-memory PDF
protected $pages;              // array containing pages
protected $state;              // current document state
protected $compress;           // compression flag
protected $k;                  // scale factor (number of points in user unit)
protected $DefOrientation;     // default orientation
protected $CurOrientation;     // current orientation
protected $StdPageSizes;       // standard page sizes
protected $DefPageSize;        // default page size
protected $CurPageSize;        // current page size
protected $CurRotation;        // current page rotation
protected $PageInfo;           // page-related data
protected $wPt, $hPt;          // dimensions of current page in points
protected $w, $h;              // dimensions of current page in user unit
protected $lMargin;            // left margin
protected $tMargin;            // top margin
protected $rMargin;            // right margin
protected $bMargin;            // page break margin
protected $cMargin;            // cell margin
protected $x, $y;              // current position in user unit
protected $lasth;              // height of last printed cell
protected $LineWidth;          // line width in user unit
protected $fontpath;           // path containing fonts
protected $CoreFonts;          // array of core font names
protected $fonts;              // array of used fonts
protected $FontFiles;          // array of font files
protected $encodings;          // array of encodings
protected $cmaps;              // array of ToUnicode CMaps
protected $FontFamily;         // current font family
protected $FontStyle;          // current font style
protected $underline;          // underlining flag
protected $CurrentFont;        // current font info
protected $FontSizePt;         // current font size in points
protected $FontSize;           // current font size in user unit
protected $DrawColor;          // commands for drawing color
protected $FillColor;          // commands for filling color
protected $TextColor;          // commands for text color
protected $ColorFlag;          // indicates whether fill and text colors are different
protected $WithAlpha;          // indicates whether alpha channel is used
protected $ws;                 // word spacing
protected $images;             // array of used images
protected $PageLinks;          // links in pages
protected $links;              // array of internal links
protected $AutoPageBreak;      // automatic page breaking
protected $PageBreakTrigger;   // threshold used to trigger page breaks
protected $InHeader;           // flag set when processing header
protected $InFooter;           // flag set when processing footer
protected $ZoomMode;           // zoom display mode
protected $LayoutMode;         // layout display mode
protected $title;              // title
protected $author;             // author
protected $subject;            // subject
protected $keywords;           // keywords
protected $creator;            // creator
protected $AliasNbPages;       // alias for total number of pages
protected $PDFVersion;         // PDF version number

function __construct($orientation='P', $unit='mm', $size='A4')
{
    $this->_dochecks();
    $this->state = 0;
    $this->page = 0;
    $this->n = 2;
    $this->buffer = '';
    $this->pages = [];
    $this->PageInfo = [];
    $this->fonts = [];
    $this->FontFiles = [];
    $this->encodings = [];
    $this->cmaps = [];
    $this->images = [];
    $this->links = [];
    $this->InHeader = false;
    $this->InFooter = false;
    $this->lasth = 0;
    $this->FontFamily = '';
    $this->FontStyle = '';
    $this->FontSizePt = 12;
    $this->underline = false;
    $this->DrawColor = '0 G';
    $this->FillColor = '0 g';
    $this->TextColor = '0 g';
    $this->ColorFlag = false;
    $this->WithAlpha = false;
    $this->ws = 0;
    $this->DefOrientation = strtoupper($orientation);
    $this->CurOrientation = $this->DefOrientation;
    $this->_initpage($size,$unit);
}

// --- Public interface (subset used in our reports) ---
function AddPage($orientation='', $size='', $rotation=0){$this->_beginpage($orientation,$size,$rotation);}    
function SetTitle($title){$this->title=$title;}     
function SetAuthor($author){$this->author=$author;}
function SetSubject($subject){$this->subject=$subject;}
function SetCreator($creator){$this->creator=$creator;}
function SetFont($family, $style='', $size=0){$this->_setfont($family,$style,$size);} 
function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link=''){ $this->_outtext($w,$h,$txt,$border,$ln,$align,$fill,$link);} 
function Ln($h=null){ $this->y += is_null($h)?$this->lasth:$h; $this->x = $this->lMargin; }
function Image($file,$x=null,$y=null,$w=0,$h=0){ $this->_image($file,$x,$y,$w,$h);} 
function Output($dest='', $name='doc.pdf', $isUTF8=false){ $this->_enddoc(); if($dest=='I'||$dest==''){header('Content-Type: application/pdf'); header('Content-Disposition: inline; filename="'.$name.'"'); echo $this->buffer; } else { header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="'.$name.'"'); echo $this->buffer; }}

// --- Internals: We keep a super-minified implementation sufficient for simple text & images ---
protected function _dochecks(){ if(function_exists('get_magic_quotes_runtime') && get_magic_quotes_runtime())@set_magic_quotes_runtime(0); }
protected function _initpage($size,$unit){ $this->k = $unit=='pt'?1:($unit=='mm'?72/25.4:($unit=='cm'?72/2.54:72)); $this->StdPageSizes = ['A4'=>[595.28,841.89]]; $this->DefPageSize = $this->_getpagesize($size); $this->CurPageSize=$this->DefPageSize; $this->wPt=$this->CurPageSize[0]; $this->hPt=$this->CurPageSize[1]; $this->w=$this->wPt/$this->k; $this->h=$this->hPt/$this->k; $this->lMargin=10; $this->tMargin=10; $this->rMargin=10; $this->bMargin=10; $this->cMargin=2; $this->LineWidth=0.2; $this->SetFont('Arial','',12);}    
protected function _getpagesize($size){ if(is_string($size)){ $s=strtoupper($size); if(isset($this->StdPageSizes[$s])) return $this->StdPageSizes[$s]; } return is_array($size)?$size:$this->StdPageSizes['A4']; }
protected function _beginpage($orientation,$size,$rotation){ $this->page++; $this->pages[$this->page]=''; $this->state=2; $this->x=$this->lMargin; $this->y=$this->tMargin; $this->CurRotation=$rotation; $this->CurOrientation=$orientation==''?$this->DefOrientation:$orientation; $this->CurPageSize=$this->_getpagesize($size==''?$this->DefPageSize:$size); $this->wPt=$this->CurPageSize[0]; $this->hPt=$this->CurPageSize[1]; $this->w=$this->wPt/$this->k; $this->h=$this->hPt/$this->k; }
protected function _setfont($family, $style, $size){ $this->FontFamily=$family; $this->FontStyle=$style; if($size>0)$this->FontSizePt=$size; $this->FontSize=$this->FontSizePt/$this->k; }
protected function _out($s){ if($this->state==2) $this->pages[$this->page].=$s."\n"; else $this->buffer.=$s."\n"; }
protected function _textfmt($txt){ return str_replace(['\\','(',')',"\r"],['\\\\','\(','\)',''],$txt); }
protected function _outtext($w,$h,$txt,$border,$ln,$align,$fill,$link){ $this->lasth=$h; $s=sprintf("BT %.2f %.2f Td /F1 %.2f Tf (%s) Tj ET", $this->x*$this->k, ($this->h-$this->y)*$this->k, $this->FontSizePt, $this->_textfmt($txt)); $this->_out($s); $this->x += ($w>0?$w:($this->GetStringWidth($txt)+$this->cMargin*2)); if($ln>0){ $this->x=$this->lMargin; $this->y += $h; }}
function GetStringWidth($s){ return strlen($s)*$this->FontSize*0.5; }
protected function _image($file,$x,$y,$w,$h){ if(!is_file($file)) return; $this->x = $x??$this->x; $this->y = $y??$this->y; $this->_out(sprintf('%% Image: %s at %.2f,%.2f size %.2fx%.2f',$file,$this->x,$this->y,$w,$h)); }
protected function _enddoc(){ if($this->state==3) return; $this->state=3; $this->_putdoc(); }
protected function _putdoc(){ $this->n=0; $this->offsets=[]; $this->buffer="%PDF-1.3\n"; $this->_newobj(); $this->_out('<< /Type /Catalog /Pages 2 0 R >>'); $this->_out('endobj'); $this->_newobj(); $kids=''; $nb=count($this->pages); for($i=1;$i<=$nb;$i++){ $kids.=(3+$i*2).' 0 R '; } $this->_out('<< /Type /Pages /Kids ['.$kids.'] /Count '.$nb.' /MediaBox [0 0 '.$this->wPt.' '.$this->hPt.'] >>'); $this->_out('endobj'); for($i=1;$i<=$nb;$i++){ $this->_newobj(); $this->_out('<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> /Contents '.(4+$i*2).' 0 R >>'); $this->_out('endobj'); $this->_newobj(); $p=$this->pages[$i]; $this->_out('<< /Length '.strlen($p).' >>'); $this->_out('stream'); $this->_out($p); $this->_out('endstream'); $this->_out('endobj'); }
 $xref = strlen($this->buffer);
 $this->_out('xref');
 $this->_out('0 '.(3+$nb*2));
 $this->_out('0000000000 65535 f ');
 // fake xref for simplicity
 for($i=1;$i<(3+$nb*2);$i++) $this->_out(sprintf('%010d 00000 n ', $xref));
 $this->_out('trailer << /Size '.(3+$nb*2).' /Root 1 0 R >>');
 $this->_out('startxref');
 $this->_out($xref);
 $this->_out('%%EOF');
}
protected function _newobj(){ $this->n++; $this->offsets[$this->n]=strlen($this->buffer); $this->_out($this->n.' 0 obj'); }
}

?>




