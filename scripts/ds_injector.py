#!/usr/bin/env python

import base64
import datetime
import argparse
from lxml import etree as ET
from lxml.etree import QName
import mimetypes
import logging

# Setting up basic logging
logging.basicConfig(level=logging.INFO)

# Format the XML based on depth level.
def format_xml_element(element, level=0, indent="  "):
    spacing = "\n" + level * indent

    if len(element):
        if not element.text or not element.text.strip():
            element.text = spacing + indent
        if not element.tail or not element.tail.strip():
            element.tail = spacing
        for child in element:
            format_xml_element(child, level + 1, indent)
    else:
        if level and (not element.tail or not element.tail.strip()):
            element.tail = spacing

# Apply propery indentation and line breaks to the XML.
def prettify_xml(root):
    format_xml_element(root)

# This function encodes the content into base64 and calculates the original binary size.
def compress_and_encode(file_path):
    with open(file_path, 'rb') as f_in:
        binary_data = f_in.read()
        original_size = len(binary_data)
        base64_data = base64.b64encode(binary_data)
        base64_lines = [base64_data[i:i+80].decode('utf-8') for i in range(0, len(base64_data), 80)]
        indented_base64 = '\n              '.join(base64_lines)
        return indented_base64, original_size

# This function registers all namespaces within the FOXML.
def register_namespaces(xml_path):
    try:
        namespaces = dict([node for _, node in ET.iterparse(xml_path, events=['start-ns'])])
        for ns in namespaces:
            ET.register_namespace(ns, namespaces[ns])
    except Exception as e:
        logging.error(f'Error registering namespaces: {e}')
        raise

# Creates a new DS entry for the specific DSID.
def add_datastream_version(xml_path, dsid, base64_data, original_size, mimetype, label=None):
    try:
        root = ET.parse(xml_path).getroot()
    except ET.ParseError as e:
        logging.error(f'XML parsing error: {e}')
        return

    nsmap = {
      'foxml': 'info:fedora/fedora-system:def/foxml#',
      'xsi': 'http://www.w3.org/2001/XMLSchema-instance',
      'audit': 'info:fedora/fedora-system:def/audit#',
      'rdf': 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
      'fedora': 'info:fedora/fedora-system:def/relations-external#',
      'fedora-model': 'info:fedora/fedora-system:def/model#',
      'islandora': 'http://islandora.ca/ontology/relsext#'
    }

    # Have to use qualified names when creating an element.
    ds_version_tag = QName(nsmap['foxml'], 'datastreamVersion')
    binary_content_tag = QName(nsmap['foxml'], 'binaryContent')

    datastream = root.find(f".//foxml:datastream[@ID='{dsid}']", namespaces=nsmap)
    if datastream is None:
        logging.warning(f'Datastream with ID of {dsid} does not exist.')
        return

    if label is None:
        datastream_version = datastream.find('.//foxml:datastreamVersion[last()]', namespaces=nsmap)
        label = datastream_version.get('LABEL') if datastream_version is not None else 'default_label'

    new_id = '{}.{}'.format(dsid, len(datastream.findall('.//foxml:datastreamVersion', namespaces=nsmap)))
    datastream_version = ET.SubElement(datastream, ds_version_tag, {
        'ID': new_id,
        'LABEL': label,
        'MIMETYPE': mimetype,
        'SIZE': str(original_size)
    })

    dt = datetime.datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%S.%f')[:-3]+'Z'
    datastream_version.set('CREATED', dt)

    binary_content = ET.SubElement(datastream_version, binary_content_tag)
    binary_content.text = '\n    ' + base64_data + '\n    '

    try:
        ET.indent(root, space="  ")  # Indents the XML for better readability
        prettify_xml(root)
        xml_string = ET.tostring(root, encoding='utf-8', method='xml', xml_declaration=True)
    except Exception as e:
        logging.error(f'Error creating XML string: {e}')
        raise

    return xml_string

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--xml', help='path to the XML file to modify', required=True)
    parser.add_argument('--dsid', help='ID of the datastream to modify', required=True)
    parser.add_argument('--content', help='path to the binary content to add as a new datastreamVersion', required=True)
    parser.add_argument('--label', help='label of the new datastream version')
    parser.add_argument('--output', help='path to the output XML file', required=True)
    args = parser.parse_args()

    try:
        mimetype, _ = mimetypes.guess_type(args.content)
        mimetype = mimetype or 'application/octet-stream'

        base64_data, original_size = compress_and_encode(args.content)
        register_namespaces(args.xml)
        updated_xml = add_datastream_version(args.xml, args.dsid, base64_data, original_size, mimetype, args.label)

        if updated_xml:
            with open(args.output, 'w') as f_out:
                f_out.write(updated_xml.decode('utf-8'))
    except Exception as e:
        logging.error(f'Error in script execution: {e}')
