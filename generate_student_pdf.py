#!/usr/bin/env python3
"""
Professional PDF Generator for Student Applications
Uses ReportLab for high-quality PDF generation with proper page breaks
"""

import json
import sys
from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.units import mm
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    PageBreak, Image, KeepTogether, HRFlowable
)
from reportlab.pdfgen import canvas
from datetime import datetime
import os


class StudentApplicationPDF:
    def __init__(self, student_data, output_filename):
        self.student_data = student_data
        self.output_filename = output_filename
        self.page_width, self.page_height = A4
        self.styles = getSampleStyleSheet()
        self._setup_custom_styles()
        
    def _setup_custom_styles(self):
        """Setup custom paragraph styles"""
        # Title style
        self.styles.add(ParagraphStyle(
            name='CustomTitle',
            parent=self.styles['Heading1'],
            fontSize=24,
            textColor=colors.HexColor('#1e40af'),
            spaceAfter=6,
            alignment=TA_CENTER,
            fontName='Helvetica-Bold'
        ))
        
        # Subtitle style
        self.styles.add(ParagraphStyle(
            name='CustomSubtitle',
            parent=self.styles['Heading2'],
            fontSize=16,
            textColor=colors.HexColor('#64748b'),
            spaceAfter=20,
            alignment=TA_CENTER,
            fontName='Helvetica'
        ))
        
        # Section header style
        self.styles.add(ParagraphStyle(
            name='SectionHeader',
            parent=self.styles['Heading2'],
            fontSize=14,
            textColor=colors.white,
            spaceAfter=12,
            spaceBefore=15,
            alignment=TA_LEFT,
            fontName='Helvetica-Bold',
            backColor=colors.HexColor('#3b82f6'),
            leftIndent=10,
            rightIndent=10,
            borderPadding=(8, 8, 8, 8)
        ))
        
        # Label style
        self.styles.add(ParagraphStyle(
            name='FieldLabel',
            parent=self.styles['Normal'],
            fontSize=10,
            textColor=colors.HexColor('#475569'),
            spaceAfter=3,
            fontName='Helvetica-Bold'
        ))
        
        # Field value style
        self.styles.add(ParagraphStyle(
            name='FieldValue',
            parent=self.styles['Normal'],
            fontSize=11,
            textColor=colors.HexColor('#1e293b'),
            spaceAfter=12,
            fontName='Helvetica',
            backColor=colors.HexColor('#f8fafc'),
            borderColor=colors.HexColor('#e2e8f0'),
            borderWidth=1,
            borderPadding=8,
            leftIndent=8,
            rightIndent=8
        ))
        
    def _create_field(self, label, value):
        """Create a labeled field"""
        elements = []
        elements.append(Paragraph(label.upper(), self.styles['FieldLabel']))
        elements.append(Paragraph(str(value) if value else '-', self.styles['FieldValue']))
        return elements
    
    def _create_section_header(self, title):
        """Create a section header with blue background"""
        return Paragraph(title, self.styles['SectionHeader'])
    
    def _add_page_number(self, canvas, doc, page_num, total_pages):
        """Add page number at bottom"""
        canvas.saveState()
        canvas.setFont('Helvetica', 9)
        canvas.setFillColor(colors.HexColor('#666666'))
        text = f"Page {page_num} of {total_pages}"
        canvas.drawCentredString(self.page_width / 2, 15 * mm, text)
        canvas.restoreState()
    
    def generate(self):
        """Generate the complete PDF"""
        doc = SimpleDocTemplate(
            self.output_filename,
            pagesize=A4,
            rightMargin=15*mm,
            leftMargin=15*mm,
            topMargin=15*mm,
            bottomMargin=20*mm
        )
        
        story = []
        
        # Page 1: Personal Information and Programme Details
        story.extend(self._create_page1())
        story.append(PageBreak())
        
        # Page 2: Educational Qualifications
        story.extend(self._create_page2())
        story.append(PageBreak())
        
        # Page 3: Terms and Conditions
        story.extend(self._create_page3())
        
        # Build PDF
        doc.build(story)
        
    def _create_page1(self):
        """Create page 1: Personal and Programme Information"""
        elements = []
        data = self.student_data
        
        # Header
        elements.append(Paragraph("STUDENT APPLICATION FORM", self.styles['CustomTitle']))
        elements.append(Paragraph("Registration Details", self.styles['CustomSubtitle']))
        elements.append(Spacer(1, 10*mm))
        
        # Photo (if available)
        photo_path = data.get('photo_path', '')
        if photo_path and os.path.exists(photo_path):
            try:
                img = Image(photo_path, width=40*mm, height=48*mm)
                img.hAlign = 'CENTER'
                elements.append(img)
                elements.append(Spacer(1, 8*mm))
            except Exception as e:
                print(f"Warning: Could not load photo: {e}", file=sys.stderr)
        
        # Personal Information Section
        elements.append(self._create_section_header("PERSONAL INFORMATION"))
        elements.append(Spacer(1, 5*mm))
        
        # Create table for personal info (3 columns)
        personal_data = [
            [
                self._create_field("Title", data.get('title', '')),
                self._create_field("First Name", data.get('firstname', '')),
                self._create_field("Last Name", data.get('lastname', ''))
            ]
        ]
        
        # Full name and certificate name row
        elements.extend(self._create_field("Full Name", data.get('fullname', '')))
        elements.extend(self._create_field("Name on Certificate", data.get('certificate_name', '')))
        
        # DOB, Nationality, Gender row
        elements.extend(self._create_field("Date of Birth", data.get('dob', '')))
        elements.extend(self._create_field("Nationality", data.get('nationality', '')))
        elements.extend(self._create_field("Gender", data.get('gender', '')))
        
        # NIC and Passport
        elements.extend(self._create_field("NIC", data.get('nic', '')))
        elements.extend(self._create_field("Passport", data.get('passport', '')))
        
        elements.append(Spacer(1, 5*mm))
        
        # Contact Information Section
        elements.append(self._create_section_header("CONTACT INFORMATION"))
        elements.append(Spacer(1, 5*mm))
        
        elements.extend(self._create_field("Permanent Address", data.get('permanent_address', '')))
        elements.extend(self._create_field("Current Address", data.get('current_address', '')))
        elements.extend(self._create_field("Mobile", data.get('mobile', '')))
        elements.extend(self._create_field("Home Number", data.get('home_number', '')))
        elements.extend(self._create_field("Office Number", data.get('office_number', '')))
        elements.extend(self._create_field("Email", data.get('email', '')))
        elements.extend(self._create_field("Emergency Contact", data.get('emergency_contact', '')))
        
        elements.append(Spacer(1, 5*mm))
        
        # Programme Information Section
        elements.append(self._create_section_header("PROGRAMME INFORMATION"))
        elements.append(Spacer(1, 5*mm))
        
        elements.extend(self._create_field("Programme", data.get('program', '')))
        elements.extend(self._create_field("Batch", data.get('batch', '')))
        
        # Payment Information (if available)
        if data.get('batch_details'):
            elements.append(Spacer(1, 5*mm))
            elements.append(self._create_section_header("PAYMENT INFORMATION"))
            elements.append(Spacer(1, 5*mm))
            
            bd = data['batch_details']
            elements.extend(self._create_field("Course Fee (LKR)", bd.get('course_fee_lkr', '')))
            elements.extend(self._create_field("Registration Fee", bd.get('registration_fee', '')))
            elements.extend(self._create_field("Installments", bd.get('installment_no', '')))
            elements.extend(self._create_field("Register Date", bd.get('register_date', '')))
        
        return elements
    
    def _create_page2(self):
        """Create page 2: Educational Qualifications"""
        elements = []
        data = self.student_data
        qualifications = data.get('qualifications', {})
        
        elements.append(self._create_section_header("EDUCATIONAL QUALIFICATIONS"))
        elements.append(Spacer(1, 8*mm))
        
        # O/L Results
        if qualifications.get('ol'):
            elements.append(Paragraph("O/L Results", self.styles['Heading3']))
            elements.append(Spacer(1, 3*mm))
            
            table_data = [['Subject', 'Grade', 'Year', 'School']]
            for ol in qualifications['ol']:
                table_data.append([
                    ol.get('subject', ''),
                    ol.get('grade', ''),
                    ol.get('exam_year', ''),
                    ol.get('school', '')
                ])
            
            table = Table(table_data, colWidths=[45*mm, 25*mm, 25*mm, 75*mm])
            table.setStyle(TableStyle([
                ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#3b82f6')),
                ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
                ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
                ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('FONTSIZE', (0, 0), (-1, 0), 11),
                ('BOTTOMPADDING', (0, 0), (-1, 0), 8),
                ('TOPPADDING', (0, 0), (-1, 0), 8),
                ('BACKGROUND', (0, 1), (-1, -1), colors.HexColor('#f8fafc')),
                ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#e2e8f0')),
                ('FONTSIZE', (0, 1), (-1, -1), 10),
                ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.HexColor('#f8fafc'), colors.white])
            ]))
            elements.append(table)
            elements.append(Spacer(1, 8*mm))
        
        # A/L Results
        if qualifications.get('al'):
            elements.append(Paragraph("A/L Results", self.styles['Heading3']))
            elements.append(Spacer(1, 3*mm))
            
            table_data = [['Subject', 'Grade', 'Year', 'School']]
            for al in qualifications['al']:
                table_data.append([
                    al.get('subject', ''),
                    al.get('grade', ''),
                    al.get('exam_year', ''),
                    al.get('school', '')
                ])
            
            table = Table(table_data, colWidths=[45*mm, 25*mm, 25*mm, 75*mm])
            table.setStyle(TableStyle([
                ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#3b82f6')),
                ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
                ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
                ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('FONTSIZE', (0, 0), (-1, 0), 11),
                ('BOTTOMPADDING', (0, 0), (-1, 0), 8),
                ('TOPPADDING', (0, 0), (-1, 0), 8),
                ('BACKGROUND', (0, 1), (-1, -1), colors.HexColor('#f8fafc')),
                ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#e2e8f0')),
                ('FONTSIZE', (0, 1), (-1, -1), 10),
                ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.HexColor('#f8fafc'), colors.white])
            ]))
            elements.append(table)
            elements.append(Spacer(1, 8*mm))
        
        # Academic Qualifications
        if qualifications.get('academic'):
            elements.append(Paragraph("Academic Qualifications", self.styles['Heading3']))
            elements.append(Spacer(1, 3*mm))
            
            table_data = [['Qualification', 'Institution', 'Year']]
            for acad in qualifications['academic']:
                table_data.append([
                    acad.get('qualification', ''),
                    acad.get('institution', ''),
                    acad.get('year', '')
                ])
            
            table = Table(table_data, colWidths=[60*mm, 80*mm, 30*mm])
            table.setStyle(TableStyle([
                ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#3b82f6')),
                ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
                ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
                ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('FONTSIZE', (0, 0), (-1, 0), 11),
                ('BOTTOMPADDING', (0, 0), (-1, 0), 8),
                ('TOPPADDING', (0, 0), (-1, 0), 8),
                ('BACKGROUND', (0, 1), (-1, -1), colors.HexColor('#f8fafc')),
                ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#e2e8f0')),
                ('FONTSIZE', (0, 1), (-1, -1), 10),
                ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.HexColor('#f8fafc'), colors.white])
            ]))
            elements.append(table)
            elements.append(Spacer(1, 8*mm))
        
        # Other Qualifications
        if qualifications.get('other'):
            elements.append(Paragraph("Other Qualifications", self.styles['Heading3']))
            elements.append(Spacer(1, 3*mm))
            
            for other in qualifications['other']:
                elements.append(Paragraph(
                    other.get('details', ''),
                    self.styles['FieldValue']
                ))
                elements.append(Spacer(1, 3*mm))
        
        return elements
    
    def _create_page3(self):
        """Create page 3: Terms and Conditions"""
        elements = []
        
        elements.append(self._create_section_header("TERMS AND CONDITIONS"))
        elements.append(Spacer(1, 8*mm))
        
        terms_style = ParagraphStyle(
            name='Terms',
            parent=self.styles['Normal'],
            fontSize=10,
            spaceAfter=6,
            leftIndent=15,
            bulletIndent=10
        )
        
        elements.append(Paragraph(
            "Please read and understand the following terms:",
            self.styles['Normal']
        ))
        elements.append(Spacer(1, 3*mm))
        
        terms = [
            "Course fees paid are not refundable under any circumstances.",
            "Course fee may be transferred, under special circumstances, from one course to another in favour of the same student.",
            "The Management reserves the right to alter the timetable at any time after the commencement of the course.",
            "Students must abide by the Student Charter, regulations, rules and dress code of BMS.",
            "Student exam admission and/or results may be withheld for non-payment of the course fee installment on due date.",
            "The qualification can only be awarded after all assessment requirements have been met and all fees have been paid to BMS."
        ]
        
        for term in terms:
            elements.append(Paragraph(f"• {term}", terms_style))
        
        elements.append(Spacer(1, 8*mm))
        
        # Declaration box
        declaration_style = ParagraphStyle(
            name='Declaration',
            parent=self.styles['Normal'],
            fontSize=10,
            alignment=TA_LEFT,
            backColor=colors.HexColor('#e0f2fe'),
            borderColor=colors.HexColor('#bae6fd'),
            borderWidth=1,
            borderPadding=10,
            leftIndent=10,
            rightIndent=10
        )
        
        elements.append(Paragraph(
            "I confirm that the information given in this form is correct and complete. "
            "I have read and understood the terms and conditions and agreed to abide by "
            "the terms and conditions set out above, which I accept as conditions of this application.",
            declaration_style
        ))
        
        elements.append(Spacer(1, 15*mm))
        
        # Signature section
        signature_data = [
            ["Student Signature: ___________________________", f"Date: {datetime.now().strftime('%Y-%m-%d')}"]
        ]
        
        sig_table = Table(signature_data, colWidths=[90*mm, 80*mm])
        sig_table.setStyle(TableStyle([
            ('ALIGN', (0, 0), (0, 0), 'LEFT'),
            ('ALIGN', (1, 0), (1, 0), 'RIGHT'),
            ('FONTSIZE', (0, 0), (-1, -1), 10),
            ('TOPPADDING', (0, 0), (-1, -1), 20)
        ]))
        elements.append(sig_table)
        
        return elements


def main():
    """Main function to generate PDF from JSON input"""
    if len(sys.argv) < 2:
        print("Usage: python generate_pdf.py <json_file>", file=sys.stderr)
        sys.exit(1)
    
    json_file = sys.argv[1]
    
    try:
        with open(json_file, 'r') as f:
            student_data = json.load(f)
    except Exception as e:
        print(f"Error reading JSON file: {e}", file=sys.stderr)
        sys.exit(1)
    
    output_filename = student_data.get('output_filename', 'student_application.pdf')
    
    try:
        pdf_generator = StudentApplicationPDF(student_data, output_filename)
        pdf_generator.generate()
        print(f"PDF generated successfully: {output_filename}")
    except Exception as e:
        print(f"Error generating PDF: {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()
