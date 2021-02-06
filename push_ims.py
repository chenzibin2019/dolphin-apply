import requests, pymysql, os

print('==== Processing IMS File =====')

IMS_DIR = /path/to/ims/enterprise/file

ims_data = {}
membership_head = {}
membership_body = {}
people_body = {}

db = pymysql.connect(DB_HOST,DB_USER,DB_PSWD,DB_NAME)
cursor = db.cursor()

for ims in os.listdir(IMS_DIR):
	tokens = ims.split('.')
	mid = int(tokens[0])
	sid = int(tokens[1])
	f = open('%s/%s' % (IMS_DIR, ims), 'r')
	ims_content = f.read()
	f.close()
	if mid not in ims_data:
		ims_data[mid] = ''
		membership_body[mid] = ''
		membership_head[mid] = ''
		people_body[mid] = ''
	cursor.execute('select ims_course_id from bks_apply_model where id=%d' % mid)
	data, = cursor.fetchone()
	membership_head[mid] = '<membership><sourcedid><source>MDI_{}</source><id>{}</id></sourcedid>'.format(mid, data)
	people_content, membership_content = ims_content.split('|')
	ims_data[mid] += membership_content
	people_body[mid] += people_content
	if "<status>1</status>" in membership_content:
		cursor.execute('update bks_apply_student_%d set has_ims_acc=1 where id=%d' % (mid, sid))
	else:
		cursor.execute('update bks_apply_student_%d set has_ims_acc=0 where id=%d' % (mid, sid))
	db.commit()
	os.remove('%s/%s' % (IMS_DIR, ims))
		
if ims_data != {}:
	ims_push = '<enterprise>'
	
	for m in ims_data:
		ims_push += people_body[m]
		ims_push += membership_head[m]
		ims_push += ims_data[m]
		ims_push += '</membership>'
	
	ims_push += '</enterprise>'
	print(ims_push)
	
	url = 'https://training.sdnuxmt.cn/ims_push.php'
	myobj = {'token': 'HouH6kWjjyXg70Yc1Dbvzi0pJXoq3Xf0', 'ims_content': ims_push}
	
	x = requests.post(url, data = myobj)
	print('Done,', x.text)
else:
	print('Done, nothing to push')
		
