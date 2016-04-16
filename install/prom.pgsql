-- QM Promisance - Turn-based strategy game
-- Copyright (C) QMT Productions
--
-- $Id: prom.pgsql 1983 2014-10-01 15:18:43Z quietust $

DROP TABLE IF EXISTS {CLAN};
DROP SEQUENCE IF EXISTS {CLAN}_seq;
CREATE SEQUENCE {CLAN}_seq;
CREATE TABLE {CLAN} (
    c_id integer PRIMARY KEY DEFAULT nextval('{CLAN}_seq'),
    c_name varchar(8) NOT NULL DEFAULT '',
    c_password varchar(255) NOT NULL DEFAULT '',
    c_members smallint NOT NULL DEFAULT 0,
    e_id_leader integer NOT NULL DEFAULT 0,
    e_id_asst integer NOT NULL DEFAULT 0,
    e_id_fa1 integer NOT NULL DEFAULT 0,
    e_id_fa2 integer NOT NULL DEFAULT 0,
    c_title varchar(255) NOT NULL DEFAULT '',
    c_url varchar(255) NOT NULL DEFAULT '',
    c_pic varchar(255) NOT NULL DEFAULT ''
);
CREATE INDEX {CLAN}_c_name ON {CLAN} (c_name);
CREATE INDEX {CLAN}_c_members ON {CLAN} (c_members);

DROP TABLE IF EXISTS {CLAN_INVITE};
DROP SEQUENCE IF EXISTS {CLAN_INVITE}_seq;
CREATE SEQUENCE {CLAN_INVITE}_seq;
CREATE TABLE {CLAN_INVITE} (
    ci_id integer PRIMARY KEY DEFAULT nextval('{CLAN_INVITE}_seq'),
    c_id integer NOT NULL DEFAULT 0,
    e_id_1 integer NOT NULL DEFAULT 0,
    e_id_2 integer NOT NULL DEFAULT 0,
    ci_flags smallint NOT NULL DEFAULT 0,
    ci_time integer NOT NULL DEFAULT 0
);
CREATE INDEX {CLAN_INVITE}_c_id ON {CLAN_INVITE} (c_id);
CREATE INDEX {CLAN_INVITE}_e_id_2 ON {CLAN_INVITE} (e_id_2);

DROP TABLE IF EXISTS {CLAN_MESSAGE};
DROP SEQUENCE IF EXISTS {CLAN_MESSAGE}_seq;
CREATE SEQUENCE {CLAN_MESSAGE}_seq;
CREATE TABLE {CLAN_MESSAGE} (
    cm_id integer PRIMARY KEY DEFAULT nextval('{CLAN_MESSAGE}_seq'),
    ct_id integer NOT NULL DEFAULT 0,
    e_id integer NOT NULL DEFAULT 0,
    cm_body text NOT NULL DEFAULT '',
    cm_time integer NOT NULL DEFAULT 0,
    cm_flags smallint NOT NULL DEFAULT 0
);
CREATE INDEX {CLAN_MESSAGE}_e_id ON {CLAN_MESSAGE} (e_id);
CREATE INDEX {CLAN_MESSAGE}_cm_time ON {CLAN_MESSAGE} (cm_time);

DROP TABLE IF EXISTS {CLAN_NEWS};
DROP SEQUENCE IF EXISTS {CLAN_NEWS}_seq;
CREATE SEQUENCE {CLAN_NEWS}_seq;
CREATE TABLE {CLAN_NEWS} (
    cn_id integer PRIMARY KEY DEFAULT nextval('{CLAN_NEWS}_seq'),
    cn_time integer NOT NULL DEFAULT 0,
    c_id integer NOT NULL DEFAULT 0,
    e_id_1 integer NOT NULL DEFAULT 0,
    c_id_2 integer NOT NULL DEFAULT 0,
    e_id_2 integer NOT NULL DEFAULT 0,
    cn_event smallint NOT NULL DEFAULT 0
);
CREATE INDEX {CLAN_NEWS}_c_id ON {CLAN_NEWS} (c_id);
CREATE INDEX {CLAN_NEWS}_e_id_1 ON {CLAN_NEWS} (e_id_1);
CREATE INDEX {CLAN_NEWS}_c_id_2 ON {CLAN_NEWS} (c_id_2);
CREATE INDEX {CLAN_NEWS}_e_id_2 ON {CLAN_NEWS} (e_id_2);
CREATE INDEX {CLAN_NEWS}_cn_event ON {CLAN_NEWS} (cn_event);

DROP TABLE IF EXISTS {CLAN_RELATION};
DROP SEQUENCE IF EXISTS {CLAN_RELATION}_seq;
CREATE SEQUENCE {CLAN_RELATION}_seq;
CREATE TABLE {CLAN_RELATION} (
    cr_id integer PRIMARY KEY DEFAULT nextval('{CLAN_RELATION}_seq'),
    c_id_1 integer NOT NULL DEFAULT 0,
    c_id_2 integer NOT NULL DEFAULT 0,
    cr_flags smallint NOT NULL DEFAULT 0,
    cr_time integer NOT NULL DEFAULT 0
);
CREATE INDEX {CLAN_RELATION}_c_id_1 ON {CLAN_RELATION} (c_id_1);
CREATE INDEX {CLAN_RELATION}_c_id_2 ON {CLAN_RELATION} (c_id_2);
CREATE INDEX {CLAN_RELATION}_cr_flags ON {CLAN_RELATION} (cr_flags);

DROP TABLE IF EXISTS {CLAN_TOPIC};
DROP SEQUENCE IF EXISTS {CLAN_TOPIC}_seq;
CREATE SEQUENCE {CLAN_TOPIC}_seq;
CREATE TABLE {CLAN_TOPIC} (
    ct_id integer PRIMARY KEY DEFAULT nextval('{CLAN_TOPIC}_seq'),
    c_id integer NOT NULL DEFAULT 0,
    ct_subject varchar(255) NOT NULL DEFAULT '',
    ct_flags smallint NOT NULL DEFAULT 0
);
CREATE INDEX {CLAN_TOPIC}_c_id ON {CLAN_TOPIC} (c_id);

DROP TABLE IF EXISTS {EMPIRE};
DROP SEQUENCE IF EXISTS {EMPIRE}_seq;
CREATE SEQUENCE {EMPIRE}_seq;
CREATE TABLE {EMPIRE} (
    e_id integer PRIMARY KEY DEFAULT nextval('{EMPIRE}_seq'),
    u_id integer NOT NULL DEFAULT 0,
    u_oldid integer NOT NULL DEFAULT 0,
    e_signupdate integer NOT NULL DEFAULT 0,
    e_flags smallint NOT NULL DEFAULT 0,
    e_valcode varchar(32) NOT NULL DEFAULT '',
    e_reason varchar(255) NOT NULL DEFAULT '',
    e_vacation smallint NOT NULL DEFAULT 0,
    e_idle integer NOT NULL DEFAULT 0,
    e_name varchar(255) NOT NULL DEFAULT '',
    e_race smallint NOT NULL DEFAULT 0,
    e_era smallint NOT NULL DEFAULT 0,
    e_rank integer NOT NULL DEFAULT 0,
    c_id integer NOT NULL DEFAULT 0,
    c_oldid integer NOT NULL DEFAULT 0,
    e_sharing smallint NOT NULL DEFAULT 0,
    e_attacks smallint NOT NULL DEFAULT 0,
    e_offsucc smallint NOT NULL DEFAULT 0,
    e_offtotal smallint NOT NULL DEFAULT 0,
    e_defsucc smallint NOT NULL DEFAULT 0,
    e_deftotal smallint NOT NULL DEFAULT 0,
    e_kills smallint NOT NULL DEFAULT 0,
    e_score integer NOT NULL DEFAULT 0,
    e_killedby integer NOT NULL DEFAULT 0,
    e_killclan integer NOT NULL DEFAULT 0,
    e_turns integer NOT NULL DEFAULT 0,
    e_storedturns integer NOT NULL DEFAULT 0,
    e_turnsused integer NOT NULL DEFAULT 0,
    e_networth bigint NOT NULL DEFAULT 0,
    e_cash bigint NOT NULL DEFAULT 0,
    e_food bigint NOT NULL DEFAULT 0,
    e_peasants bigint NOT NULL DEFAULT 0,
    e_trparm bigint NOT NULL DEFAULT 0,
    e_trplnd bigint NOT NULL DEFAULT 0,
    e_trpfly bigint NOT NULL DEFAULT 0,
    e_trpsea bigint NOT NULL DEFAULT 0,
    e_trpwiz bigint NOT NULL DEFAULT 0,
    e_health smallint NOT NULL DEFAULT 0,
    e_runes bigint NOT NULL DEFAULT 0,
    e_indarm smallint NOT NULL DEFAULT 0,
    e_indlnd smallint NOT NULL DEFAULT 0,
    e_indfly smallint NOT NULL DEFAULT 0,
    e_indsea smallint NOT NULL DEFAULT 0,
    e_land integer NOT NULL DEFAULT 0,
    e_bldpop integer NOT NULL DEFAULT 0,
    e_bldcash integer NOT NULL DEFAULT 0,
    e_bldtrp integer NOT NULL DEFAULT 0,
    e_bldcost integer NOT NULL DEFAULT 0,
    e_bldwiz integer NOT NULL DEFAULT 0,
    e_bldfood integer NOT NULL DEFAULT 0,
    e_blddef integer NOT NULL DEFAULT 0,
    e_freeland integer NOT NULL DEFAULT 0,
    e_tax smallint NOT NULL DEFAULT 0,
    e_bank bigint NOT NULL DEFAULT 0,
    e_loan bigint NOT NULL DEFAULT 0,
    e_mktarm bigint NOT NULL DEFAULT 0,
    e_mktlnd bigint NOT NULL DEFAULT 0,
    e_mktfly bigint NOT NULL DEFAULT 0,
    e_mktsea bigint NOT NULL DEFAULT 0,
    e_mktfood bigint NOT NULL DEFAULT 0,
    e_mktperarm smallint NOT NULL DEFAULT 0,
    e_mktperlnd smallint NOT NULL DEFAULT 0,
    e_mktperfly smallint NOT NULL DEFAULT 0,
    e_mktpersea smallint NOT NULL DEFAULT 0
);
CREATE INDEX {EMPIRE}_u_id ON {EMPIRE} (u_id);
CREATE INDEX {EMPIRE}_u_oldid ON {EMPIRE} (u_oldid);
CREATE INDEX {EMPIRE}_e_flags ON {EMPIRE} (e_flags);
CREATE INDEX {EMPIRE}_c_id ON {EMPIRE} (c_id);

DROP TABLE IF EXISTS {EMPIRE_EFFECT};
CREATE TABLE {EMPIRE_EFFECT} (
    e_id integer NOT NULL DEFAULT 0,
    ef_name varchar(255) NOT NULL DEFAULT '',
    ef_value integer NOT NULL DEFAULT 0,
    PRIMARY KEY (e_id, ef_name)
);

DROP TABLE IF EXISTS {EMPIRE_MESSAGE};
DROP SEQUENCE IF EXISTS {EMPIRE_MESSAGE}_seq;
CREATE SEQUENCE {EMPIRE_MESSAGE}_seq;
CREATE TABLE {EMPIRE_MESSAGE} (
    m_id integer PRIMARY KEY DEFAULT nextval('{EMPIRE_MESSAGE}_seq'),
    m_id_ref integer NOT NULL DEFAULT 0,
    m_time integer NOT NULL DEFAULT 0,
    e_id_src integer NOT NULL DEFAULT 0,
    e_id_dst integer NOT NULL DEFAULT 0,
    m_subject varchar(255) NOT NULL DEFAULT '',
    m_body text NOT NULL DEFAULT '',
    m_flags smallint NOT NULL DEFAULT 0
);
CREATE INDEX {EMPIRE_MESSAGE}_m_time ON {EMPIRE_MESSAGE} (m_time);
CREATE INDEX {EMPIRE_MESSAGE}_e_id_src ON {EMPIRE_MESSAGE} (e_id_src);
CREATE INDEX {EMPIRE_MESSAGE}_e_id_dst ON {EMPIRE_MESSAGE} (e_id_dst);
CREATE INDEX {EMPIRE_MESSAGE}_m_flags ON {EMPIRE_MESSAGE} (m_flags);

DROP TABLE IF EXISTS {EMPIRE_NEWS};
DROP SEQUENCE IF EXISTS {EMPIRE_NEWS}_seq;
CREATE SEQUENCE {EMPIRE_NEWS}_seq;
CREATE TABLE {EMPIRE_NEWS} (
    n_id integer PRIMARY KEY DEFAULT nextval('{EMPIRE_NEWS}_seq'),
    n_time integer NOT NULL DEFAULT 0,
    e_id_src integer NOT NULL DEFAULT 0,
    c_id_src integer NOT NULL DEFAULT 0,
    e_id_dst integer NOT NULL DEFAULT 0,
    c_id_dst integer NOT NULL DEFAULT 0,
    n_event smallint NOT NULL DEFAULT 0,
    n_d0 bigint NOT NULL DEFAULT 0,
    n_d1 bigint NOT NULL DEFAULT 0,
    n_d2 bigint NOT NULL DEFAULT 0,
    n_d3 bigint NOT NULL DEFAULT 0,
    n_d4 bigint NOT NULL DEFAULT 0,
    n_d5 bigint NOT NULL DEFAULT 0,
    n_d6 bigint NOT NULL DEFAULT 0,
    n_d7 bigint NOT NULL DEFAULT 0,
    n_d8 bigint NOT NULL DEFAULT 0,
    n_flags smallint NOT NULL DEFAULT 0
);
CREATE INDEX {EMPIRE_NEWS}_e_id_src ON {EMPIRE_NEWS} (e_id_src);
CREATE INDEX {EMPIRE_NEWS}_c_id_src ON {EMPIRE_NEWS} (c_id_src);
CREATE INDEX {EMPIRE_NEWS}_e_id_dst ON {EMPIRE_NEWS} (e_id_dst);
CREATE INDEX {EMPIRE_NEWS}_c_id_dst ON {EMPIRE_NEWS} (c_id_dst);
CREATE INDEX {EMPIRE_NEWS}_n_event ON {EMPIRE_NEWS} (n_event);
CREATE INDEX {EMPIRE_NEWS}_n_flags ON {EMPIRE_NEWS} (n_flags);

DROP TABLE IF EXISTS {HISTORY_CLAN};
CREATE TABLE {HISTORY_CLAN} (
    hr_id smallint NOT NULL DEFAULT 0,
    hc_id integer NOT NULL DEFAULT 0,
    hc_members smallint NOT NULL DEFAULT 0,
    hc_name varchar(8) NOT NULL DEFAULT '',
    hc_title varchar(255) NOT NULL DEFAULT '',
    hc_totalnet bigint NOT NULL DEFAULT 0,
    PRIMARY KEY (hr_id, hc_id)
);

DROP TABLE IF EXISTS {HISTORY_EMPIRE};
CREATE TABLE {HISTORY_EMPIRE} (
    hr_id smallint NOT NULL DEFAULT 0,
    he_flags smallint NOT NULL DEFAULT 0,
    u_id integer NOT NULL DEFAULT 0,
    he_id integer NOT NULL DEFAULT 0,
    he_name varchar(255) NOT NULL DEFAULT '',
    he_race varchar(64) NOT NULL DEFAULT '',
    he_era varchar(64) NOT NULL DEFAULT '',
    hc_id integer NOT NULL DEFAULT 0,
    he_offsucc smallint NOT NULL DEFAULT 0,
    he_offtotal smallint NOT NULL DEFAULT 0,
    he_defsucc smallint NOT NULL DEFAULT 0,
    he_deftotal smallint NOT NULL DEFAULT 0,
    he_kills smallint NOT NULL DEFAULT 0,
    he_score integer NOT NULL DEFAULT 0,
    he_networth bigint NOT NULL DEFAULT 0,
    he_land integer NOT NULL DEFAULT 0,
    he_rank integer NOT NULL DEFAULT 0,
    PRIMARY KEY (hr_id, he_id)
);

DROP TABLE IF EXISTS {HISTORY_ROUND};
DROP SEQUENCE IF EXISTS {HISTORY_ROUND}_seq;
CREATE SEQUENCE {HISTORY_ROUND}_seq;
CREATE TABLE {HISTORY_ROUND} (
    hr_id integer PRIMARY KEY DEFAULT nextval('{HISTORY_ROUND}_seq'),
    hr_name varchar(64) NOT NULL DEFAULT '',
    hr_description text NOT NULL DEFAULT '',
    hr_startdate integer NOT NULL DEFAULT 0,
    hr_stopdate integer NOT NULL DEFAULT 0,
    hr_flags smallint NOT NULL DEFAULT 0,
    hr_smallclansize smallint NOT NULL DEFAULT 0,
    hr_smallclans smallint NOT NULL DEFAULT 0,
    hr_allclans smallint NOT NULL DEFAULT 0,
    hr_nonclanempires integer NOT NULL DEFAULT 0,
    hr_liveempires integer NOT NULL DEFAULT 0,
    hr_deadempires integer NOT NULL DEFAULT 0,
    hr_delempires integer NOT NULL DEFAULT 0,
    hr_allempires integer NOT NULL DEFAULT 0
);

DROP TABLE IF EXISTS {LOCK};
CREATE TABLE {LOCK} (
    lock_type smallint NOT NULL DEFAULT 0,
    lock_id integer NOT NULL DEFAULT 0,
    PRIMARY KEY (lock_type,lock_id)
);

DROP TABLE IF EXISTS {LOG};
DROP SEQUENCE IF EXISTS {LOG}_seq;
CREATE SEQUENCE {LOG}_seq;
CREATE TABLE {LOG} (
    log_id integer PRIMARY KEY DEFAULT nextval('{LOG}_seq'),
    log_time integer NOT NULL DEFAULT 0,
    log_type integer NOT NULL DEFAULT 0,
    log_ip inet NOT NULL DEFAULT '0.0.0.0',
    log_page varchar(32) NOT NULL DEFAULT '',
    log_action varchar(64) NOT NULL DEFAULT '',
    log_locks varchar(64) NOT NULL DEFAULT '',
    log_text text NOT NULL DEFAULT '',
    u_id integer NOT NULL DEFAULT 0,
    e_id integer NOT NULL DEFAULT 0,
    c_id integer NOT NULL DEFAULT 0
);

DROP TABLE IF EXISTS {LOTTERY};
CREATE TABLE {LOTTERY} (
    e_id integer NOT NULL DEFAULT 0,
    l_ticket integer NOT NULL DEFAULT 0,
    l_cash bigint NOT NULL DEFAULT 0
);
CREATE INDEX {LOTTERY}_e_id ON {LOTTERY} (e_id);
CREATE INDEX {LOTTERY}_l_ticket ON {LOTTERY} (l_ticket);

DROP TABLE IF EXISTS {MARKET};
DROP SEQUENCE IF EXISTS {MARKET}_seq;
CREATE SEQUENCE {MARKET}_seq;
CREATE TABLE {MARKET} (
    k_id integer PRIMARY KEY DEFAULT nextval('{MARKET}_seq'),
    k_type smallint NOT NULL DEFAULT 0,
    e_id integer NOT NULL DEFAULT 0,
    k_amt bigint NOT NULL DEFAULT 0,
    k_price integer NOT NULL DEFAULT 0,
    k_time integer NOT NULL DEFAULT 0
);
CREATE INDEX {MARKET}_e_id ON {MARKET} (e_id);
CREATE INDEX {MARKET}_k_type ON {MARKET} (k_type);
CREATE INDEX {MARKET}_k_time ON {MARKET} (k_time);

DROP TABLE IF EXISTS {PERMISSION};
DROP SEQUENCE IF EXISTS {PERMISSION}_seq;
CREATE SEQUENCE {PERMISSION}_seq;
CREATE TABLE {PERMISSION} (
    p_id integer PRIMARY KEY DEFAULT nextval('{PERMISSION}_seq'),
    p_type smallint NOT NULL DEFAULT 0,
    p_criteria varchar(255) NOT NULL DEFAULT '',
    p_comment varchar(255) NOT NULL DEFAULT '',
    p_reason varchar(255) NOT NULL DEFAULT '',
    p_createtime integer NOT NULL DEFAULT 0,
    p_updatetime integer NOT NULL DEFAULT 0,
    p_lasthit integer NOT NULL DEFAULT 0,
    p_hitcount integer NOT NULL DEFAULT 0,
    p_expire integer NOT NULL DEFAULT 0
);
CREATE INDEX {PERMISSION}_p_type ON {PERMISSION} (p_type);
CREATE INDEX {PERMISSION}_p_expire ON {PERMISSION} (p_expire);

DROP TABLE IF EXISTS {SESSION};
CREATE TABLE {SESSION} (
    sess_id varchar(64) PRIMARY KEY,
    sess_time integer NOT NULL DEFAULT 0,
    sess_data text NOT NULL DEFAULT ''
);
CREATE INDEX {SESSION}_sess_time ON {SESSION} (sess_time);

DROP TABLE IF EXISTS {TURNLOG};
DROP SEQUENCE IF EXISTS {TURNLOG}_seq;
CREATE SEQUENCE {TURNLOG}_seq;
CREATE TABLE {TURNLOG} (
    turn_id integer PRIMARY KEY DEFAULT nextval('{TURNLOG}_seq'),
    turn_time integer NOT NULL DEFAULT 0,
    turn_ticks integer NOT NULL DEFAULT 0,
    turn_interval integer NOT NULL DEFAULT 0,
    turn_type smallint NOT NULL DEFAULT 0,
    turn_text text NOT NULL DEFAULT ''
);
CREATE INDEX {TURNLOG}_turn_type ON {TURNLOG} (turn_type);

DROP TABLE IF EXISTS {USER};
DROP SEQUENCE IF EXISTS {USER}_seq;
CREATE SEQUENCE {USER}_seq;
CREATE TABLE {USER} (
    u_id integer PRIMARY KEY DEFAULT nextval('{USER}_seq'),
    u_username varchar(255) NOT NULL UNIQUE DEFAULT '',
    u_password varchar(255) NOT NULL DEFAULT '',
    u_flags smallint NOT NULL DEFAULT 0,
    u_name varchar(255) NOT NULL DEFAULT '',
    u_email varchar(255) NOT NULL UNIQUE DEFAULT '',
    u_comment varchar(255) NOT NULL DEFAULT '',
    u_timezone integer NOT NULL DEFAULT 0,
    u_style varchar(32) NOT NULL DEFAULT '',
    u_lang varchar(16) NOT NULL DEFAULT '',
    u_dateformat varchar(64) NOT NULL DEFAULT '',
    u_lastip inet NOT NULL DEFAULT '0.0.0.0',
    u_kills integer NOT NULL DEFAULT 0,
    u_deaths integer NOT NULL DEFAULT 0,
    u_offsucc integer NOT NULL DEFAULT 0,
    u_offtotal integer NOT NULL DEFAULT 0,
    u_defsucc integer NOT NULL DEFAULT 0,
    u_deftotal integer NOT NULL DEFAULT 0,
    u_numplays integer NOT NULL DEFAULT 0,
    u_sucplays integer NOT NULL DEFAULT 0,
    u_avgrank double precision NOT NULL DEFAULT 0,
    u_bestrank double precision NOT NULL DEFAULT 0,
    u_createdate integer NOT NULL DEFAULT 0,
    u_lastdate integer NOT NULL DEFAULT 0
);
CREATE INDEX {USER}_u_flags ON {USER} (u_flags);

DROP TABLE IF EXISTS {VAR};
CREATE TABLE {VAR} (
    v_name varchar(255) PRIMARY KEY,
    v_value varchar(255) NOT NULL DEFAULT ''
);

DROP TABLE IF EXISTS {VAR_ADJUST};
CREATE TABLE {VAR_ADJUST} (
    v_name varchar(255) NOT NULL DEFAULT '',
    v_offset bigint NOT NULL DEFAULT 0
);
