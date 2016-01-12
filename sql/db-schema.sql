-- Table: schema_name.temp_reach_hull

-- DROP TABLE schema_name.temp_reach_hull;

CREATE TABLE IF NOT EXISTS schema_name.temp_reach_hull
(
  id integer NOT NULL,
  the_geom geometry('Polygon', 4326),
  diff geometry('Polygon', 4326),
  CONSTRAINT temp_reach_hull_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

--
-- Name: temp_reach_hull_id_seq; Type: SEQUENCE; Schema: schema_name; Owner: pgadmin
--

CREATE SEQUENCE temp_reach_hull_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
	
--
-- Name: temp_reach_hull_id_seq; Type: SEQUENCE OWNED BY; Schema: schema_name; Owner: pgadmin
--

ALTER SEQUENCE temp_reach_hull_id_seq OWNED BY temp_reach_hull.id;
ALTER TABLE schema_name.temp_reach_hull ALTER COLUMN id SET DEFAULT nextval('temp_reach_hull_id_seq'::regclass);

-- Table: schema_name.temp_reach_multipoint

-- DROP TABLE schema_name.temp_reach_multipoint;

CREATE TABLE IF NOT EXISTS schema_name.temp_reach_multipoint
(
  id integer NOT NULL,
  the_geom geometry('MultiPoint', 4326),
  CONSTRAINT temp_reach_poly_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

--
-- Name: temp_reach_multipoint_id_seq; Type: SEQUENCE; Schema: schema_name; Owner: pgadmin
--

CREATE SEQUENCE temp_reach_multipoint_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
	
--
-- Name: temp_reach_multipoint_id_seq; Type: SEQUENCE OWNED BY; Schema: schema_name; Owner: pgadmin
--

ALTER SEQUENCE temp_reach_multipoint_id_seq OWNED BY temp_reach_multipoint.id;
ALTER TABLE schema_name.temp_reach_multipoint ALTER COLUMN id SET DEFAULT nextval('temp_reach_multipoint_id_seq'::regclass);