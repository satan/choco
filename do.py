from __future__ import print_function
#
import codecs
import glob
import sys
from sys import argv
import os
import datetime
from os import walk
from os import path
from os import makedirs
#
import string
#
import json
import csv
#
import time
from datetime import date, timedelta
from datetime import datetime
#
import pymysql.cursors
import mysql.connector
from mysql.connector import errorcode
#
##
def validate_date(date_text):
	#
	got_format = '%d-%m-%Y'
	result_format = '%y-%m-%d %H:%M:%S'
	#
	try:
		#
		valid = datetime.strptime(date_text, got_format)
		return valid.strftime(result_format)
		#
	except ValueError:
		#	
		return False
		#
#
def t_c(table):
	#
	return {
		'id_akcii': 'INT(10)',
		'nazvanie_akcii': 'VARCHAR(100)',
		'data_nacala_akcii': 'DATETIME',
		'data_okoncania': 'DATETIME',
		'status': 'VARCHAR(20)'
	}[table]
	#
#
def run_db(arg, db_name, in_data):
	#
	connection = mysql.connector.connect(user='root', password='password')
	#
	cursor = connection.cursor()
	#
	sql_create = 'CREATE DATABASE '+db_name+';'
	sql_use = 'USE '+db_name+';'
	#
	if arg == 'create_database':
		#
		try:

			cursor.execute(sql_create)

		except mysql.connector.Error as err:
			#
			print(errorcode.ER_DB_CREATE_EXISTS )
			#
			if err.errno == errorcode.ER_DB_CREATE_EXISTS:
				#
				pass
				print("CANT CREATE EXISTS: "+ db_name)
				#
			else:
				#
				pass
				#
		#
	elif arg == 'create_tables':
		#
		cursor.execute(sql_use)
		#
		i = 0
		#
		for table in in_data:

			print(table+'\n')

			if i == 0:
				#
				try:
					#
					cursor.execute('''create table akcii (
											'''+in_data[0]+''' '''+t_c(in_data[0])+''',
											'''+in_data[1]+''' '''+t_c(in_data[1])+''',
											'''+in_data[2]+''' '''+t_c(in_data[2])+''',
											'''+in_data[3]+''' '''+t_c(in_data[3])+''',
											'''+in_data[4]+''' '''+t_c(in_data[4])+'''
									)  DEFAULT CHARSET=utf8;''')

				except mysql.connector.Error as err:
					#
					if(err.errno == errorcode.ER_TABLE_EXISTS_ERROR ):
						#
						print("CANT CREATE TABLE EXISTS: "+ table)
						#
						pass
				#
			#
		#
	#
	elif arg == 'insert':
		#
		cursor.execute(sql_use)
		#
		print('inserting: '+ str(tuple(in_data)) )
		#
		try:
			#
			cursor.execute("""INSERT INTO akcii VALUES """+str(tuple(in_data))+""";""")
			connection.commit()
			#
		except mysql.connector.Error as err:
			
			print(err)
			print(err.errno)
		#
	#
	connection.close()
	#

def transliterate(word, translit_table):
	converted_word = ''
	for char in word:
		transchar = ''
		if char in translit_table:
			transchar = translit_table[char]
		else:
			transchar = char
		converted_word += transchar
	return converted_word
#

def import_transliteration(transliteration):
	#
	dirname, filename = os.path.split(os.path.abspath(__file__))
	#
	with open(dirname+'/'+'translit.json', 'r') as json_file:
		#
		json_t = json.loads( json_file.read() )
		#
		selected_dict = json_t[transliteration]
		#
		res = []
		#
		for x in selected_dict:
			
			for k,v in x.items():
				
				innerdict = [(k,v)]

			res.append( innerdict[0] )
		#
		ress = {}
		#
		for x, y in res:
			
			ress[x] = y
		#
		return ress
	#
#
def init():
	#
	dirname, filename = os.path.split(os.path.abspath(__file__))
	#
	exclude = set(string.punctuation)
	#
	select_trans_iso = import_transliteration('cyrillic_translit_iso');
	select_trans_iso_atone = import_transliteration('cyrillic_translit_iso_atone');
	#
	run_db('create_database', 'py_test', None )
	#
	with open(dirname+'/'+'data.csv', 'r') as data_file:
		#
		data_read = csv.reader(data_file,delimiter=';', quotechar='"')
		#
		row = 0
		#
		for data_row in data_read:
			#
			dirty_set = data_row
			#
			clean_set = []
			#
			for str_token in dirty_set:
				#
				valid_date = validate_date(str_token)
				#
				if valid_date: # check if is date
					#
					clean_set.append(valid_date)
					#
				else:
					#
					s = ''.join(ch for ch in str_token if ch not in exclude).replace('  ',' ')
					#
					#
					if( row == 0 ):
						#
						dash_rep = '_';
						res_clean = s.replace(' ',dash_rep ).lower()
						#
						# for database tables we use iso atonal with underscore
						t = transliterate(res_clean, select_trans_iso_atone ) #
						#
					else:
						#
						dash_rep = '-';
						res_clean = s.replace(' ',dash_rep )
						#
						# for data we use iso tarnsliteration
						t = transliterate(res_clean, select_trans_iso ) #
						#

					clean_set.append(t)
				#
			#
			print(clean_set)
			# 
			if( row == 0 ):# CSV Header Responsible for tables
				#
				run_db('create_tables', 'py_test', clean_set )
				#
			else:
				#
				run_db('insert', 'py_test', clean_set )
				#
			#
			row = row + 1	
		#
		
init()
