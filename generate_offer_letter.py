
import sys
import json
import os
from datetime import datetime, timezone, timedelta
import re
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, Image
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY

class OfferLetterGenerator:
    def __init__(self, data, output_filename):
        self.data = data
        self.output_filename = output_filename
        self.styles = getSampleStyleSheet()
        self._setup_custom_styles()

    def _setup_custom_styles(self):
        """Setup custom professional paragraph styles"""
        # Rename 'Title' to 'OfferTitle' to avoid conflict with default stylesheet
        self.styles.add(ParagraphStyle(
            name='OfferTitle',
            parent=self.styles['Heading1'],
            fontSize=11,
            textColor=colors.black,
            spaceAfter=5,
            alignment=TA_LEFT,
            fontName='Helvetica-Bold'
        ))
        
        self.styles.add(ParagraphStyle(
            name='ProgramTitle',
            parent=self.styles['Heading2'],
            fontSize=13,
            textColor=colors.black,
            spaceAfter=15,
            alignment=TA_CENTER,
            fontName='Helvetica-Bold'
        ))
        
        self.styles.add(ParagraphStyle(
            name='NormalJustified',
            parent=self.styles['Normal'],
            fontSize=11,
            leading=14,
            alignment=TA_JUSTIFY,
            fontName='Helvetica'
        ))

        self.styles.add(ParagraphStyle(
            name='NormalLeft',
            parent=self.styles['Normal'],
            fontSize=11,
            leading=14,
            alignment=TA_LEFT,
            fontName='Helvetica'
        ))

    def generate(self):
        doc = SimpleDocTemplate(
            self.output_filename,
            pagesize=A4,
            rightMargin=20*mm,
            leftMargin=20*mm,
            topMargin=15*mm,
            bottomMargin=15*mm
        )
        
        story = []
        
        # --- Logo ---
        logo_path = r'c:\xampp\htdocs\ims\admin\uploads\company_profiles\bmslogo.png'
        if not os.path.exists(logo_path):
             logo_path = os.path.join(os.path.dirname(__file__), 'admin', 'uploads', 'company_profiles', 'bmslogo.png')
        
        if os.path.exists(logo_path):
            img = Image(logo_path, width=42*mm, height=16*mm) # 16mm is approx 60px
            img.hAlign = 'LEFT'
            story.append(img)
        else:
            story.append(Spacer(1, 15*mm)) 

        story.append(Spacer(1, 3.5*mm)) # Approx 10px margin-bottom
        
        # --- Date (Colombo Time: GMT+5:30) ---
        colombo_tz = timezone(timedelta(hours=5, minutes=30))
        current_date = datetime.now(colombo_tz).strftime('%d %B %Y')
        story.append(Paragraph(current_date, self.styles['NormalLeft']))
        story.append(Spacer(1, 5*mm))
        
        # --- Student Address Block ---
        fullname = self.data.get('fullname', '')
        address = self.data.get('address', '')
        # Format address: add line breaks after commas to match the preferred style
        address = re.sub(r',\s*', ',<br/>', address)
        address = address.replace('\n', '<br/>')
        
        story.append(Paragraph(f"<b>{fullname}</b><br/>{address}", self.styles['NormalLeft']))
        story.append(Spacer(1, 8*mm))
        
        # --- Salutation ---
        firstname = self.data.get('firstname', '')
        story.append(Paragraph(f"Dear {firstname},", self.styles['NormalLeft']))
        story.append(Spacer(1, 8*mm))
        
        # --- Title ---
        program = self.data.get('program', '')
        # In PHP: $conditional_offer_letter = 1 is CONDITIONAL
        raw_conditional = self.data.get('is_conditional', 0)
        try:
            # Handle potential string "0" which Python bool("0") evaluates to True
            is_conditional = int(raw_conditional)
        except (ValueError, TypeError):
            # Fallback for non-integer strings like "false", "", etc
            is_conditional = 0
            
        # Fix logic: If is_conditional is true (1), it should be CONDITIONAL.
        cond_label = "CONDITIONAL" if is_conditional else "UNCONDITIONAL"
        title_text = f"<u>{cond_label} OFFER - {program.upper()}</u>"
        story.append(Paragraph(title_text, self.styles['OfferTitle']))
        story.append(Spacer(1, 2*mm))
        
        # --- Intro ---
        # Logic for body text
        conditions = self.data.get('conditions', '')
        
        cond_text = "conditional" if is_conditional else "unconditional"
        attendance = self.data.get('attendance', 'full time')
        intro_text = f"Thank you for your application for admission to Business Management School. I am pleased to offer you a <b>{cond_text}</b> place on the {attendance.lower()} taught programme specified above. Details of your programme, important dates, fees and cost are as follows:"
        story.append(Paragraph(intro_text, self.styles['NormalJustified']))
        story.append(Spacer(1, 4*mm))
        
        # --- Details Table ---
        fee_str = self.data.get('fee_string', '')
        batch_intake = self.data.get('batch_intake', 'TBA')
        attendance = self.data.get('attendance', 'N/A')
        awarded_by = self.data.get('awarded_by', 'N/A')
        qualification_level = self.data.get('qualification_level', 'N/A')
        duration = self.data.get('duration', 'TBA')
        nic = self.data.get('nic', '-')
        
        table_data = [
            ['Duration', duration],
            ['Student NIC', nic],
            ['Programme Intake', batch_intake],
            ['Attendance', attendance],
            ['Awarded by', awarded_by]
        ]

        # Conditional rows based on program - matching online_registration_upload_process.php precisely
        if program == 'Executive Certificate in Management':
             table_data.append(['Recognized By', self.data.get('recognized_by', '-')])
        
        # Note: PHP has 'Higher Diploma in Biotechnologys' with an 's'
        elif program in ['Higher Diploma in Biomedical Science', 'Higher Diploma in Biotechnology']:
             table_data.append(['Accredited By', self.data.get('accredited_by', '-')])
        
        elif program in [
            'Graduate Diploma in Management (Level 6)',
            'International Foundation Diploma (Applied Science) - ATHE Level 3',
            'International Foundation Diploma (Business) - ATHE Level 3',
            'BTEC Higher National Diploma in Business'
        ]:
            table_data.append(['Qualification Level', qualification_level])

        table_data.append(['Programme Fee', fee_str])
        
        t = Table(table_data, colWidths=[60*mm, 110*mm])
        t.setStyle(TableStyle([
            ('FONTNAME', (0,0), (0,-1), 'Helvetica-Bold'),
            ('FONTNAME', (1,0), (1,-1), 'Helvetica'),
            ('FONTSIZE', (0,0), (-1,-1), 10),
            ('BACKGROUND', (0,0), (0,-1), colors.HexColor('#f8fafc')),
            ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor('#e2e8f0')),
            ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
            ('padding', (0,0), (-1,-1), 4), # Reduced padding
        ]))
        story.append(t)
        story.append(Spacer(1, 4*mm))

        if is_conditional:
            all_conditions = self.data.get('all_conditions', [])
            
            # If all_conditions not pre-populated list, try to build it from individual fields
            if not all_conditions:
                # Check for individual condition fields 1-4
                c1 = self.data.get('conditional_offer_letter_text', '')
                c2 = self.data.get('conditional_offer_letter_text_02', '')
                c3 = self.data.get('conditional_offer_letter_text_03', '')
                c4 = self.data.get('conditional_offer_letter_text_04', '')
                
                if c1: all_conditions.append(c1)
                if c2: all_conditions.append(c2)
                if c3: all_conditions.append(c3)
                if c4: all_conditions.append(c4)
            
            # Fallback to single 'conditions' field if still empty
            if not all_conditions and conditions:
                all_conditions = [conditions]
            
            if all_conditions:
                story.append(Paragraph("<b>CONDITIONS</b>", self.styles['NormalJustified']))
                # Use bullet points for conditions
                for cond in all_conditions:
                    # Strip whitespace and check if not empty
                    if str(cond).strip():
                        story.append(Paragraph(f"• {str(cond).strip()}", self.styles['NormalJustified']))
                story.append(Spacer(1, 4*mm))
        
        # --- Payment Info & Extra Paragraphs ---
        extra_paragraphs = self.data.get('extra_paragraphs', [])
        for para in extra_paragraphs:
            story.append(Paragraph(para, self.styles['NormalJustified']))
            story.append(Spacer(1, 4*mm))
        
        deadline = self.data.get('deadline', '')
        deadline_text = f"You shall accept the offer on or before <b>{deadline}</b> with the payment of the first instalment."
        story.append(Paragraph(deadline_text, self.styles['NormalJustified']))
        story.append(Spacer(1, 10*mm))
        
        # --- Sign Off ---
        story.append(Paragraph("With best wishes,", self.styles['NormalLeft']))
        story.append(Spacer(1, 15*mm)) # Space for signature
        story.append(Paragraph("<b>Academic Registrar,</b><br/>BMS", self.styles['NormalLeft']))
        
        doc.build(story)


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python generate_offer_letter.py <json_data_file>")
        sys.exit(1)
        
    json_file = sys.argv[1]
    
    try:
        with open(json_file, 'r') as f:
            data = json.load(f)
            
        output_pdf = data.get('output_pdf_path', 'offer_letter.pdf')
        
        generator = OfferLetterGenerator(data, output_pdf)
        generator.generate()
        
        print(f"PDF generated successfully: {output_pdf}")
        
    except Exception as e:
        print(f"Error: {str(e)}")
        sys.exit(1)
