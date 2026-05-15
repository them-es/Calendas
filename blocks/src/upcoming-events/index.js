import { registerBlockType } from "@wordpress/blocks";
import Edit from "./edit";
import metadata from "./block.json";
import "./style.scss";

const { name } = metadata;

registerBlockType(
	{ name, ...metadata },
	{
		edit: Edit,
		save: () => null,
	}
);
