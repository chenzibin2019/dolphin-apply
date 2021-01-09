from xml.etree.ElementTree import Element, SubElement, ElementTree, tostring
import requests, pymysql
from xml.dom import minidom

db = pymysql.connect("localhost","bkzspy","86180685","bkzspy")
cursor = db.cursor()

cursor.execute('select id,ims_min_status,ims_course_id,ims_student_email,ims_student_id,ims_student_name from bks_apply_model where write_ims=1')
models = cursor.fetchall()

process = 0

enterprise = Element('enterprise')

for mid,ims_min_status,ims_course_id,ims_student_email,ims_student_id,ims_student_name in models:
	try:
		cursor.execute('select id,status,has_ims_acc,%s,%s,%s from bks_apply_student_%d' % (ims_student_email,ims_student_id,ims_student_name,mid))
		row = cursor.fetchone()
		membership = SubElement(enterprise, 'membership')
		sourcedid = SubElement(membership, 'sourcedid')
		source = SubElement(sourcedid, 'source')
		source.text = 'MDI_%d' % mid
		id_t = SubElement(sourcedid, 'id')
		id_t.text = ims_course_id
		while row:
			# check if correct. 
			s_id, s_status, s_has_ims_acc, s_ims_student_email, s_ims_student_id, s_ims_student_name = row
			if s_status >= ims_min_status and s_has_ims_acc == 0:
				print('add', s_ims_student_name, 'to', mid)
				process += 1
				person = SubElement(enterprise, 'person')
				sourcedid = SubElement(person, 'sourcedid')
				source = SubElement(sourcedid, 'source')
				source.text = 'STD_ADD_%d_%s' % (mid, s_ims_student_id)
				id_t = SubElement(sourcedid, 'id')
				id_t.text = s_ims_student_id
				userid = SubElement(person, 'userid')
				userid.text = s_ims_student_id
				name = SubElement(person, 'name')
				fn = SubElement(name, 'fn')
				fn.text = s_ims_student_name
				n = SubElement(name, 'n')
				family = SubElement(n, 'family')
				family.text = s_ims_student_name[:1]
				given = SubElement(n, 'given')
				given.text = s_ims_student_name[1:]
				email = SubElement(person, 'email')
				email.text = s_ims_student_email
				member = SubElement(membership, 'member')
				sourcedid = SubElement(member, 'sourcedid')
				source = SubElement(sourcedid, 'source')
				source.text = 'COURSE_REG_%s_%s' % (ims_course_id, s_ims_student_id)
				id_t = SubElement(sourcedid, 'id')
				id_t.text = s_ims_student_id
				role = SubElement(member, 'role')
				role.attrib['roletype'] = '01'
				status = SubElement(role, 'status')
				status.text = '1'
				cursor.execute('update bks_apply_student_%d set has_ims_acc=1 where id=%d' % (mid, s_id))
				db.commit()
			elif s_status < ims_min_status and s_has_ims_acc == 1:
				print('withdraw', s_ims_student_name, 'from', mid)
				process += 1
				member = SubElement(membership, 'member')
				sourcedid = SubElement(member, 'sourcedid')
				source = SubElement(sourcedid, 'source')
				source.text = 'COURSE_WDR_%s_%s' % (ims_course_id, s_ims_student_id)
				id_t = SubElement(sourcedid, 'id')
				id_t.text = s_ims_student_id
				role = SubElement(member, 'role')
				role.attrib['roletype'] = '01'
				status = SubElement(role, 'status')
				status.text = '0'
				cursor.execute('update bks_apply_student_%d set has_ims_acc=0 where id=%d' % (mid, s_id))
				db.commit()
				
			row = cursor.fetchone()
	except:
		continue

if process > 0:	
	tree = ElementTree(enterprise)	
	ims_push = tostring(enterprise, 'unicode', method="xml")
	url = 'https://training.sdnuxmt.cn/ims_push.php'
	myobj = {'token': 'HouH6kWjjyXg70Yc1Dbvzi0pJXoq3Xf0', 'ims_content': ims_push}
	
	x = requests.post(url, data = myobj)
	print('Done,', x.text)
else:
	print('Done, nothing to push')





